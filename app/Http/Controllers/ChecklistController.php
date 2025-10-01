<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Amenity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ChecklistController extends Controller
{
    public function show(Checklist $checklist)
    {
        // Only checkers can view their assigned checklists
        if (auth()->guard('checker')->check() && $checklist->mission->checker_id === auth()->id()) {
            $checklist->load('checklistItems.amenity.amenityType');
            return view('checker.checklists.show', compact('checklist'));
        }

        abort(403);
    }

    public function update(Request $request, Checklist $checklist)
    {
        // Only checkers can update their assigned checklists
        if (auth()->guard('checker')->check() && $checklist->mission->checker_id === auth()->id()) {
            $request->validate([
                'items.*.state' => ['required', Rule::in(['bad', 'average', 'good', 'excellent', 'need_a_fix'])],
                'items.*.comment' => ['nullable', 'string'],
                'items.*.photo' => ['nullable', 'image', 'max:2048'], // Max 2MB
                'signature_data' => ['nullable', 'string'],
            ]);

            foreach ($request->items as $itemId => $itemData) {
                $checklistItem = ChecklistItem::findOrFail($itemId);
                $checklistItem->state = $itemData['state'];
                $checklistItem->comment = $itemData['comment'] ?? null;

                if (isset($itemData['photo'])) {
                    $path = $itemData['photo']->store('checklist_photos', 'public');
                    $checklistItem->photo_path = $path;
                }
                $checklistItem->save();
            }

            if ($request->filled('signature_data')) {
                $data = $request->input('signature_data');
                $data = str_replace('data:image/png;base64,', '', $data);
                $data = str_replace(' ', '+', $data);
                $imageName = 'signatures/' . uniqid() . '.png';
                Storage::disk('public')->put($imageName, base64_decode($data));
                $checklist->signature_path = $imageName;
            }

            $checklist->save();

            return back()->with('success', 'Checklist updated successfully.');
        }

        abort(403);
    }

    public function submit(Request $request, Checklist $checklist)
    {
        // Only checkers can submit their assigned checklists
        if (auth()->guard('checker')->check() && $checklist->mission->checker_id === auth()->id()) {
            // Ensure all items are filled before submission
            foreach ($checklist->checklistItems as $item) {
                if (empty($item->state)) {
                    return back()->withErrors(['message' => 'Please fill all checklist items before submitting.']);
                }
            }

            $checklist->status = 'completed';
            $checklist->submitted_at = now();
            $checklist->save();

            return redirect()->route('checker.dashboard')->with('success', 'Checklist submitted successfully.');
        }

        abort(403);
    }
}
