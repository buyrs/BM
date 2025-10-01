<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use Illuminate\Http\Request;

class GuestChecklistController extends Controller
{
    public function show(Checklist $checklist, string $token)
    {
        // Validate the token (e.g., against a stored token in the checklist or a related model)
        // For now, a simple check. In a real app, this token should be securely generated and stored.
        if ($checklist->guest_token !== $token) {
            abort(403, 'Invalid or expired token.');
        }

        $checklist->load('checklistItems.amenity.amenityType');
        return view('guest.checklists.show', compact('checklist', 'token'));
    }
}
