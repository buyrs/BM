<?php

namespace App\Http\Controllers;

use App\Models\AmenityType;
use Illuminate\Http\Request;

class AmenityTypeController extends Controller
{
    public function index()
    {
        if (auth()->guard('admin')->check()) {
            $amenityTypes = AmenityType::all();
            return view('admin.amenity_types.index', compact('amenityTypes'));
        }
        abort(403);
    }

    public function create()
    {
        if (auth()->guard('admin')->check()) {
            return view('admin.amenity_types.create');
        }
        abort(403);
    }

    public function store(Request $request)
    {
        if (auth()->guard('admin')->check()) {
            $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:amenity_types'],
            ]);

            AmenityType::create($request->all());

            return redirect()->route('admin.amenity_types.index')->with('success', 'Amenity Type created successfully.');
        }
        abort(403);
    }

    public function edit(AmenityType $amenityType)
    {
        if (auth()->guard('admin')->check()) {
            return view('admin.amenity_types.edit', compact('amenityType'));
        }
        abort(403);
    }

    public function update(Request $request, AmenityType $amenityType)
    {
        if (auth()->guard('admin')->check()) {
            $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:amenity_types,name,' . $amenityType->id],
            ]);

            $amenityType->update($request->all());

            return redirect()->route('admin.amenity_types.index')->with('success', 'Amenity Type updated successfully.');
        }
        abort(403);
    }

    public function destroy(AmenityType $amenityType)
    {
        if (auth()->guard('admin')->check()) {
            $amenityType->delete();
            return redirect()->route('admin.amenity_types.index')->with('success', 'Amenity Type deleted successfully.');
        }
        abort(403);
    }
}
