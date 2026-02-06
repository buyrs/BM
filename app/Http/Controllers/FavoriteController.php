<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Property;
use App\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Get all favorites for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->input('type'); // Optional filter by type

        $query = $user->favorites()->with('favorable');

        if ($type) {
            $modelClass = $this->getModelClass($type);
            if ($modelClass) {
                $query->where('favorable_type', $modelClass);
            }
        }

        $favorites = $query->latest()->get()->map(function ($favorite) {
            return [
                'id' => $favorite->id,
                'type' => class_basename($favorite->favorable_type),
                'favorable_id' => $favorite->favorable_id,
                'favorable' => $favorite->favorable,
                'created_at' => $favorite->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'favorites' => $favorites,
        ]);
    }

    /**
     * Toggle favorite status for a model.
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:property,mission',
            'id' => 'required|integer',
        ]);

        $user = $request->user();
        $modelClass = $this->getModelClass($request->input('type'));
        
        if (!$modelClass) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid type specified.',
            ], 400);
        }

        $model = $modelClass::find($request->input('id'));

        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found.',
            ], 404);
        }

        $isFavorited = $user->toggleFavorite($model);

        return response()->json([
            'success' => true,
            'is_favorited' => $isFavorited,
            'message' => $isFavorited ? 'Added to favorites!' : 'Removed from favorites.',
        ]);
    }

    /**
     * Check if a model is favorited by the authenticated user.
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:property,mission',
            'id' => 'required|integer',
        ]);

        $user = $request->user();
        $modelClass = $this->getModelClass($request->input('type'));
        
        if (!$modelClass) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid type specified.',
            ], 400);
        }

        $model = $modelClass::find($request->input('id'));

        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'is_favorited' => $user->hasFavorited($model),
        ]);
    }

    /**
     * Get the model class for a given type.
     */
    private function getModelClass(string $type): ?string
    {
        return match ($type) {
            'property' => Property::class,
            'mission' => Mission::class,
            default => null,
        };
    }
}
