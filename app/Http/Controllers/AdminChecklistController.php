<?php

namespace App\Http\Controllers;

use App\Mail\GuestChecklistMail;
use App\Models\Checklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminChecklistController extends Controller
{
    public function sendToGuest(Request $request, Checklist $checklist)
    {
        $request->validate([
            'guest_email' => ['required', 'email'],
        ]);

        // Generate a unique token for guest access
        $token = Str::random(60);
        $checklist->guest_token = $token;
        $checklist->save();

        // Create the guest access URL
        $guestUrl = route('guest.checklists.show', ['checklist' => $checklist->id, 'token' => $token]);

        // Send the email
        Mail::to($request->guest_email)->send(new GuestChecklistMail($checklist, $guestUrl));

        return back()->with('success', 'Checklist link sent to guest successfully.');
    }
}
