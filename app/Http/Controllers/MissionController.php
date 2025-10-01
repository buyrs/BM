<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\User;
use App\Models\Checklist;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MissionController extends Controller
{
    public function index()
    {
        if (auth()->guard('admin')->check()) {
            $missions = Mission::with(['admin', 'ops', 'checker'])->get();
            return view('admin.missions.index', compact('missions'));
        }

        if (auth()->guard('ops')->check()) {
            $missions = Mission::where('ops_id', auth()->id())
                                ->orWhere('admin_id', auth()->id())
                                ->with(['admin', 'ops', 'checker'])
                                ->get();
            return view('ops.missions.index', compact('missions'));
        }

        abort(403);
    }

    public function create()
    {
        if (auth()->guard('ops')->check()) {
            $checkers = User::where('role', 'checker')->get();
            return view('ops.missions.create', compact('checkers'));
        }

        abort(403);
    }

    public function store(Request $request)
    {
        if (auth()->guard('ops')->check()) {
            $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'property_address' => ['required', 'string', 'max:255'],
                'checkin_date' => ['required', 'date'],
                'checkout_date' => ['required', 'date', 'after_or_equal:checkin_date'],
                'checker_id' => ['required', 'exists:users,id', Rule::in(User::where('role', 'checker')->pluck('id'))],
            ]);

            $mission = Mission::create([
                'title' => $request->title,
                'description' => $request->description,
                'property_address' => $request->property_address,
                'checkin_date' => $request->checkin_date,
                'checkout_date' => $request->checkout_date,
                'ops_id' => auth()->id(),
                'checker_id' => $request->checker_id,
                'status' => 'pending',
            ]);

            // Create check-in and check-out checklists
            Checklist::create([
                'mission_id' => $mission->id,
                'type' => 'checkin',
                'status' => 'pending',
            ]);
            Checklist::create([
                'mission_id' => $mission->id,
                'type' => 'checkout',
                'status' => 'pending',
            ]);

            return redirect()->route('ops.missions.index')->with('success', 'Mission created successfully and awaiting admin approval.');
        }

        abort(403);
    }

    public function show(Mission $mission)
    {
        $mission->load(['admin', 'ops', 'checker', 'checklists']);

        if (auth()->guard('admin')->check() || (auth()->guard('ops')->check() && ($mission->ops_id === auth()->id() || $mission->admin_id === auth()->id()))) {
            return view('missions.show', compact('mission'));
        }

        abort(403);
    }

    public function edit(Mission $mission)
    {
        if (auth()->guard('admin')->check() || (auth()->guard('ops')->check() && $mission->ops_id === auth()->id())) {
            $checkers = User::where('role', 'checker')->get();
            return view('missions.edit', compact('mission', 'checkers'));
        }

        abort(403);
    }

    public function update(Request $request, Mission $mission)
    {
        if (auth()->guard('admin')->check() || (auth()->guard('ops')->check() && $mission->ops_id === auth()->id())) {
            $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'property_address' => ['required', 'string', 'max:255'],
                'checkin_date' => ['required', 'date'],
                'checkout_date' => ['required', 'date', 'after_or_equal:checkin_date'],
                'checker_id' => ['required', 'exists:users,id', Rule::in(User::where('role', 'checker')->pluck('id'))],
                'status' => ['required', 'string', Rule::in(['pending', 'approved', 'in_progress', 'completed', 'cancelled'])],
            ]);

            $mission->update($request->all());

            return back()->with('success', 'Mission updated successfully.');
        }

        abort(403);
    }

    public function approve(Mission $mission)
    {
        if (auth()->guard('admin')->check()) {
            $mission->status = 'approved';
            $mission->admin_id = auth()->id();
            $mission->save();
            return back()->with('success', 'Mission approved successfully.');
        }

        abort(403);
    }

    public function destroy(Mission $mission)
    {
        if (auth()->guard('admin')->check() || (auth()->guard('ops')->check() && $mission->ops_id === auth()->id())) {
            $mission->delete();
            return back()->with('success', 'Mission deleted successfully.');
        }

        abort(403);
    }
}
