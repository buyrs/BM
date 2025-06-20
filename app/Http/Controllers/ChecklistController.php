<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\ChecklistPhoto;
use App\Models\Mission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Exception;

class ChecklistController extends Controller
{
    public function create(Mission $mission)
    {
        try {
            $this->authorize('create', [Checklist::class, $mission]);

            $checklist = Checklist::where('mission_id', $mission->id)->first();
            if (!$checklist) {
                $checklist = Checklist::create([
                    'mission_id' => $mission->id,
                    'general_info' => (new Checklist)->getDefaultStructure()['general_info'],
                    'rooms' => (new Checklist)->getDefaultStructure()['rooms'],
                    'utilities' => (new Checklist)->getDefaultStructure()['utilities'],
                    'status' => 'draft'
                ]);
            }

            return Inertia::render('Checklists/Edit', [
                'mission' => $mission->load('agent'),
                'checklist' => $checklist->load('items.photos'),
            ]);
        } catch (Exception $e) {
            Log::error('Checklist creation failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to create checklist. Please try again.');
        }
    }

    public function store(Request $request, Mission $mission)
    {
        try {
            $this->authorize('create', [Checklist::class, $mission]);

            $validated = $request->validate([
                'general_info' => 'required|array',
                'rooms' => 'required|array',
                'utilities' => 'required|array',
                'items' => 'array',
                'tenant_signature' => 'nullable|string|regex:/^data:image\/[a-zA-Z]+;base64,/',
                'agent_signature' => 'nullable|string|regex:/^data:image\/[a-zA-Z]+;base64,/',
            ]);

            $checklist = Checklist::where('mission_id', $mission->id)->first();
            if (!$checklist) {
                $checklist = new Checklist();
                $checklist->mission_id = $mission->id;
            }

            $checklist->general_info = $validated['general_info'];
            $checklist->rooms = $validated['rooms'];
            $checklist->utilities = $validated['utilities'];
            $checklist->tenant_signature = $validated['tenant_signature'];
            $checklist->agent_signature = $validated['agent_signature'];
            $checklist->status = $request->input('is_draft', true) ? 'draft' : 'completed';
            $checklist->save();

            // Handle checklist items
            if ($request->has('items')) {
                foreach ($request->items as $itemData) {
                    $item = ChecklistItem::updateOrCreate(
                        [
                            'checklist_id' => $checklist->id,
                            'category' => $itemData['category'],
                            'item_name' => $itemData['item_name']
                        ],
                        [
                            'condition' => $itemData['condition'],
                            'comment' => $itemData['comment']
                        ]
                    );

                    // Handle photos
                    if (isset($itemData['photos'])) {
                        foreach ($itemData['photos'] as $photo) {
                            if (isset($photo['data'])) {
                                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $photo['data']));
                                $path = Storage::disk('public')->put(
                                    "checklist_photos/{$checklist->id}",
                                    $imageData
                                );
                                
                                ChecklistPhoto::create([
                                    'checklist_item_id' => $item->id,
                                    'photo_path' => $path
                                ]);
                            }
                        }
                    }
                }
            }

            if ($checklist->status === 'completed') {
                $mission->update(['status' => 'completed']);
            }

            return redirect()->route('missions.show', $mission)
                ->with('success', 'Checklist ' . ($checklist->status === 'draft' ? 'saved as draft' : 'completed successfully'));
        } catch (Exception $e) {
            Log::error('Checklist storage failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to save checklist. Please try again.');
        }
    }

    public function show(Mission $mission, Checklist $checklist)
    {
        $this->authorize('view', $checklist);

        return Inertia::render('Checklists/Show', [
            'mission' => $mission->load('agent'),
            'checklist' => $checklist->load('items.photos'),
        ]);
    }

    public function review(Mission $mission, Checklist $checklist)
    {
        $this->authorize('view', $checklist);

        return Inertia::render('Checklists/Review', [
            'mission' => $mission->load('agent'),
            'checklist' => $checklist->load('items.photos'),
        ]);
    }

    public function addRoom(Request $request, Checklist $checklist)
    {
        $this->authorize('update', $checklist);

        $validated = $request->validate([
            'room_type' => 'required|string',
            'room_name' => 'required|string'
        ]);

        $rooms = $checklist->rooms;
        $rooms[$validated['room_name']] = [
            'type' => $validated['room_type'],
            'walls' => null,
            'floor' => null,
            'ceiling' => null,
            'windows' => null,
            'electrical' => null,
            'heating' => null
        ];

        $checklist->update(['rooms' => $rooms]);

        return response()->json($checklist->rooms);
    }

    public function uploadPhoto(Request $request, ChecklistItem $item)
    {
        try {
            $this->authorize('update', $item->checklist);

            $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120' // 5MB max
            ]);

            $path = $request->file('photo')->store('checklist_photos/' . $item->checklist_id, 'public');

            $photo = ChecklistPhoto::create([
                'checklist_item_id' => $item->id,
                'photo_path' => $path
            ]);

            return response()->json($photo);
        } catch (Exception $e) {
            Log::error('Photo upload failed: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to upload photo'], 500);
        }
    }

    public function deletePhoto(ChecklistPhoto $photo)
    {
        try {
            $this->authorize('update', $photo->checklistItem->checklist);

            Storage::disk('public')->delete($photo->photo_path);
            $photo->delete();

            return response()->json(['message' => 'Photo deleted successfully']);
        } catch (Exception $e) {
            Log::error('Photo deletion failed: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to delete photo'], 500);
        }
    }
}