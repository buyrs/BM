<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Http\Requests\ImportPropertiesRequest;
use App\Services\PropertyCsvImportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class PropertyController extends Controller
{
    public function __construct()
    {
        // Note: Add authorization middleware based on your system
        // $this->middleware('auth:admin');
        // $this->authorizeResource(Property::class, 'property');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Property::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('property_address', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%")
                  ->orWhere('owner_address', 'like', "%{$search}%");
            });
        }

        $properties = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.properties.index', compact('properties'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.properties.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $property = Property::create($request->validated());

        return redirect()->route('admin.properties.index')
            ->with('success', 'Property created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Property $property): View
    {
        return view('admin.properties.show', compact('property'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Property $property): View
    {
        return view('admin.properties.edit', compact('property'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        $property->update($request->validated());

        return redirect()->route('admin.properties.index')
            ->with('success', 'Property updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Property $property): RedirectResponse
    {
        $property->delete();

        return redirect()->route('admin.properties.index')
            ->with('success', 'Property deleted successfully.');
    }

    /**
     * Show the CSV upload form.
     */
    public function uploadForm(): View
    {
        return view('admin.properties.upload');
    }

    /**
     * Handle CSV upload and import.
     */
    public function upload(ImportPropertiesRequest $request, PropertyCsvImportService $importService): RedirectResponse
    {
        $file = $request->file('file');
        $dryRun = $request->boolean('dry_run', false);

        $result = $importService->import($file, $dryRun);

        if ($result->hasErrors()) {
            return redirect()->back()
                ->with('error', 'Import completed with errors.')
                ->with('import_result', $result->toArray());
        }

        $message = $dryRun 
            ? 'Dry run completed successfully. No data was actually imported.'
            : 'Properties imported successfully.';

        return redirect()->back()
            ->with('success', $message)
            ->with('import_result', $result->toArray());
    }

    /**
     * Download CSV template.
     */
    public function template(PropertyCsvImportService $importService): Response
    {
        $template = $importService->generateTemplate();

        return response($template, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="properties_template.csv"',
        ]);
    }
}
