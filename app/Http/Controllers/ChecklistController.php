<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\ChecklistPhoto;
use App\Models\Mission;
use App\Services\PhotoUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Exception;

class ChecklistController extends Controller
{
    public function __construct(
        private PhotoUploadService $photoUploadService
    ) {}
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

            // Check if request expects JSON (AJAX) or wants Blade view
            if (request()->expectsJson() || request()->wantsJson()) {
                return Inertia::render('Checklists/Edit', [
                    'mission' => $mission->load('agent'),
                    'checklist' => $checklist->load('items.photos'),
                ]);
            }

            // Return Blade view for the new dynamic form
            return view('pages.checklists.edit', [
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
                'is_draft' => 'boolean',
                'photos' => 'array',
                'photos.*' => 'file|image|mimes:jpeg,png,jpg,gif,webp|max:10240' // 10MB max
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
            $checklist->status = $request->input('is_draft', true) ? 'draft' : 'submitted';
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

                    // Handle photos for this item
                    if (isset($itemData['photos'])) {
                        foreach ($itemData['photos'] as $photo) {
                            if (isset($photo['data'])) {
                                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $photo['data']));
                                $filename = 'item_' . $item->id . '_' . time() . '_' . uniqid() . '.jpg';
                                $path = "checklist_photos/{$checklist->id}/{$filename}";
                                
                                Storage::disk('public')->put($path, $imageData);
                                
                                ChecklistPhoto::create([
                                    'checklist_item_id' => $item->id,
                                    'photo_path' => $path
                                ]);
                            }
                        }
                    }
                }
            }

            // Handle direct photo uploads
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $filename = 'general_' . time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                    $path = $photo->storeAs("checklist_photos/{$checklist->id}", $filename, 'public');
                    
                    // Create a general checklist item for these photos
                    $item = ChecklistItem::firstOrCreate([
                        'checklist_id' => $checklist->id,
                        'category' => 'general',
                        'item_name' => 'Photos générales'
                    ]);
                    
                    ChecklistPhoto::create([
                        'checklist_item_id' => $item->id,
                        'photo_path' => $path
                    ]);
                }
            }

            if ($checklist->status === 'submitted') {
                $mission->update(['status' => 'completed']);
            }

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Checklist ' . ($checklist->status === 'draft' ? 'saved as draft' : 'submitted successfully'),
                    'checklist' => $checklist->load('items.photos')
                ]);
            }

            return redirect()->route('missions.show', $mission)
                ->with('success', 'Checklist ' . ($checklist->status === 'draft' ? 'saved as draft' : 'submitted successfully'));
        } catch (Exception $e) {
            Log::error('Checklist storage failed: ' . $e->getMessage());
            
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to save checklist. Please try again.',
                    'error' => $e->getMessage()
                ], 500);
            }
            
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
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240' // 10MB max
            ]);

            $photo = $this->photoUploadService->uploadChecklistPhoto(
                $item,
                $request->file('photo'),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'photo' => $photo->load('uploadedBy'),
                'url' => $this->photoUploadService->getPhotoUrl($photo)
            ]);
        } catch (Exception $e) {
            Log::error('Photo upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deletePhoto(ChecklistPhoto $photo)
    {
        try {
            $this->authorize('update', $photo->checklistItem->checklist);

            $success = $this->photoUploadService->deletePhoto($photo);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Photo deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Unable to delete photo'
                ], 500);
            }
        } catch (Exception $e) {
            Log::error('Photo deletion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPhotoUrl(ChecklistPhoto $photo, string $size = 'original')
    {
        try {
            $this->authorize('view', $photo->checklistItem->checklist);

            $url = $this->photoUploadService->getPhotoUrl($photo, $size);

            return response()->json([
                'success' => true,
                'url' => $url,
                'size' => $size
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get photo URL: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}