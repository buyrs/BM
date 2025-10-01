<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\AmenityType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AmenityController extends Controller
{
    public function index()
    {
        if (auth()->guard('admin')->check()) {
            $amenities = Amenity::with('amenityType')->get();
            return view('admin.amenities.index', compact('amenities'));
        }
        abort(403);
    }

    public function create()
    {
        if (auth()->guard('admin')->check()) {
            $amenityTypes = AmenityType::all();
            return view('admin.amenities.create', compact('amenityTypes'));
        }
        abort(403);
    }

    public function store(Request $request)
    {
        if (auth()->guard('admin')->check()) {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'amenity_type_id' => ['required', 'exists:amenity_types,id'],
                'property_id' => ['required', 'integer'], // Assuming property_id is an integer
            ]);

            Amenity::create($request->all());

            return redirect()->route('admin.amenities.index')->with('success', 'Amenity created successfully.');
        }
        abort(403);
    }

    public function edit(Amenity $amenity)
    {
        if (auth()->guard('admin')->check()) {
            $amenityTypes = AmenityType::all();
            return view('admin.amenities.edit', compact('amenity', 'amenityTypes'));
        }
        abort(403);
    }

    public function update(Request $request, Amenity $amenity)
    {
        if (auth()->guard('admin')->check()) {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'amenity_type_id' => ['required', 'exists:amenity_types,id'],
                'property_id' => ['required', 'integer'],
            ]);

            $amenity->update($request->all());

            return redirect()->route('admin.amenities.index')->with('success', 'Amenity updated successfully.');
        }
        abort(403);
    }

    public function destroy(Amenity $amenity)
    {
        if (auth()->guard('admin')->check()) {
            $amenity->delete();
            return redirect()->route('admin.amenities.index')->with('success', 'Amenity deleted successfully.');
        }
        abort(403);
    }
}
