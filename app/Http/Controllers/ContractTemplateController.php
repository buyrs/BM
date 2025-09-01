<?php

namespace App\Http\Controllers;

use App\Models\ContractTemplate;
use App\Http\Requests\ContractTemplateRequest;
use App\Http\Requests\SignatureRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ContractTemplateController extends Controller
{
    public function __construct()
    {
        // Only admins can manage contract templates
        $this->middleware('role:admin');
    }

    /**
     * Display a listing of contract templates.
     */
    public function index()
    {
        $templates = ContractTemplate::with('creator')
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/ContractTemplates/Index', [
            'templates' => $templates
        ]);
    }

    /**
     * Show the form for creating a new contract template.
     */
    public function create()
    {
        return Inertia::render('Admin/ContractTemplates/Create');
    }

    /**
     * Store a newly created contract template.
     */
    public function store(ContractTemplateRequest $request)
    {
        $validated = $request->validated();
        $validated['created_by'] = Auth::id();
        $validated['is_active'] = false; // Templates start inactive until signed

        $template = ContractTemplate::create($validated);

        return redirect()->route('admin.contract-templates.show', $template)
            ->with('success', 'Contract template created successfully. Please sign it to activate.');
    }

    /**
     * Display the specified contract template.
     */
    public function show(ContractTemplate $contractTemplate)
    {
        $contractTemplate->load('creator');

        return Inertia::render('Admin/ContractTemplates/Show', [
            'template' => $contractTemplate
        ]);
    }

    /**
     * Show the form for editing the specified contract template.
     */
    public function edit(ContractTemplate $contractTemplate)
    {
        // Can only edit if not signed yet
        if ($contractTemplate->isSignedByAdmin()) {
            return redirect()->route('admin.contract-templates.show', $contractTemplate)
                ->with('error', 'Cannot edit a signed contract template. Create a new version instead.');
        }

        return Inertia::render('Admin/ContractTemplates/Edit', [
            'template' => $contractTemplate
        ]);
    }

    /**
     * Update the specified contract template.
     */
    public function update(ContractTemplateRequest $request, ContractTemplate $contractTemplate)
    {
        // Can only update if not signed yet
        if ($contractTemplate->isSignedByAdmin()) {
            return redirect()->route('admin.contract-templates.show', $contractTemplate)
                ->with('error', 'Cannot edit a signed contract template. Create a new version instead.');
        }

        $contractTemplate->update($request->validated());

        return redirect()->route('admin.contract-templates.show', $contractTemplate)
            ->with('success', 'Contract template updated successfully.');
    }

    /**
     * Remove the specified contract template.
     */
    public function destroy(ContractTemplate $contractTemplate)
    {
        // Can only delete if not used in any signatures
        if ($contractTemplate->signatures()->exists()) {
            return redirect()->route('admin.contract-templates.index')
                ->with('error', 'Cannot delete a contract template that has been used in signatures.');
        }

        $contractTemplate->delete();

        return redirect()->route('admin.contract-templates.index')
            ->with('success', 'Contract template deleted successfully.');
    }

    /**
     * Sign the contract template with admin signature.
     */
    public function signTemplate(SignatureRequest $request, ContractTemplate $contractTemplate)
    {
        $validated = $request->validated();

        $contractTemplate->update([
            'admin_signature' => $validated['signature'],
            'admin_signed_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Contract template signed successfully.',
            'template' => $contractTemplate->fresh()
        ]);
    }

    /**
     * Activate or deactivate a contract template.
     */
    public function toggleActive(ContractTemplate $contractTemplate)
    {
        // Can only activate if signed
        if (!$contractTemplate->isSignedByAdmin() && !$contractTemplate->is_active) {
            return response()->json([
                'message' => 'Cannot activate an unsigned contract template.'
            ], 422);
        }

        $contractTemplate->update([
            'is_active' => !$contractTemplate->is_active
        ]);

        $status = $contractTemplate->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'message' => "Contract template {$status} successfully.",
            'template' => $contractTemplate->fresh()
        ]);
    }

    /**
     * Preview the contract template with sample data.
     */
    public function preview(ContractTemplate $contractTemplate)
    {
        // Sample data for preview
        $sampleData = [
            'tenant_name' => 'John Doe',
            'tenant_email' => 'john.doe@example.com',
            'tenant_phone' => '+33 1 23 45 67 89',
            'address' => '123 Rue de la Paix, 75001 Paris',
            'start_date' => Carbon::now()->format('d/m/Y'),
            'end_date' => Carbon::now()->addDays(30)->format('d/m/Y'),
            'admin_name' => Auth::user()->name,
            'admin_signature_date' => $contractTemplate->admin_signed_at ? 
                $contractTemplate->admin_signed_at->format('d/m/Y H:i') : 
                'Non signé',
        ];

        // Replace placeholders in content
        $previewContent = $this->replacePlaceholders($contractTemplate->content, $sampleData);

        return response()->json([
            'content' => $previewContent,
            'sample_data' => $sampleData,
            'has_admin_signature' => $contractTemplate->isSignedByAdmin(),
            'admin_signature' => $contractTemplate->admin_signature,
        ]);
    }

    /**
     * Create a new version of an existing contract template.
     */
    public function createVersion(ContractTemplate $contractTemplate)
    {
        $newTemplate = $contractTemplate->replicate();
        $newTemplate->name = $contractTemplate->name . ' (Version ' . Carbon::now()->format('Y-m-d H:i') . ')';
        $newTemplate->admin_signature = null;
        $newTemplate->admin_signed_at = null;
        $newTemplate->is_active = false;
        $newTemplate->created_by = Auth::id();
        $newTemplate->save();

        // Deactivate the old template
        $contractTemplate->update(['is_active' => false]);

        return redirect()->route('admin.contract-templates.edit', $newTemplate)
            ->with('success', 'New version created successfully. The previous version has been deactivated.');
    }



    /**
     * Replace placeholders in contract content with actual data.
     */
    private function replacePlaceholders(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }

    /**
     * Get contract templates for API usage (for Ops users).
     */
    public function getActiveTemplates(Request $request)
    {
        $type = $request->query('type');
        
        $query = ContractTemplate::active()->where('is_active', true);
        
        if ($type && in_array($type, ['entry', 'exit'])) {
            $query->where('type', $type);
        }

        $templates = $query->get(['id', 'name', 'type', 'content', 'admin_signature', 'admin_signed_at']);

        return response()->json([
            'templates' => $templates
        ]);
    }
}