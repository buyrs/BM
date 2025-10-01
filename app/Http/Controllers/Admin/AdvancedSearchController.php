<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdvancedSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;

class AdvancedSearchController extends Controller
{
    protected AdvancedSearchService $searchService;

    public function __construct(AdvancedSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Show the advanced search interface
     */
    public function index(): View
    {
        $savedSearches = $this->searchService->getSavedSearches();
        $analytics = $this->searchService->getSearchAnalytics();
        
        return view('admin.advanced-search.index', compact('savedSearches', 'analytics'));
    }

    /**
     * Search missions
     */
    public function searchMissions(Request $request): JsonResponse
    {
        try {
            $filters = $request->all();
            $results = $this->searchService->searchMissions($filters);

            return response()->json([
                'success' => true,
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search users
     */
    public function searchUsers(Request $request): JsonResponse
    {
        try {
            $filters = $request->all();
            $results = $this->searchService->searchUsers($filters);

            return response()->json([
                'success' => true,
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search properties
     */
    public function searchProperties(Request $request): JsonResponse
    {
        try {
            $filters = $request->all();
            $results = $this->searchService->searchProperties($filters);

            return response()->json([
                'success' => true,
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Global search
     */
    public function globalSearch(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:255'
        ]);

        try {
            $results = $this->searchService->globalSearch(
                $request->input('query'),
                $request->only(['limit', 'total_limit'])
            );

            return response()->json([
                'success' => true,
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Global search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save search
     */
    public function saveSearch(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:missions,users,properties',
            'filters' => 'required|array'
        ]);

        try {
            $search = $this->searchService->saveSearch(
                $request->input('name'),
                $request->input('type'),
                $request->input('filters')
            );

            return response()->json([
                'success' => true,
                'message' => 'Search saved successfully',
                'data' => $search
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save search: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get saved searches
     */
    public function getSavedSearches(Request $request): JsonResponse
    {
        try {
            $type = $request->input('type');
            $searches = $this->searchService->getSavedSearches($type);

            return response()->json([
                'success' => true,
                'data' => $searches
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get saved searches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete saved search
     */
    public function deleteSavedSearch(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'type' => 'required|string|in:missions,users,properties'
        ]);

        try {
            $deleted = $this->searchService->deleteSavedSearch(
                $request->input('name'),
                $request->input('type')
            );

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Search deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Search not found'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete search: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export search results
     */
    public function exportResults(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:missions,users,properties',
            'format' => 'required|string|in:csv,json',
            'filters' => 'required|array'
        ]);

        try {
            $filename = $this->searchService->exportSearchResults(
                $request->input('type'),
                $request->input('filters'),
                $request->input('format')
            );

            return response()->json([
                'success' => true,
                'message' => 'Export completed successfully',
                'data' => [
                    'filename' => $filename,
                    'download_url' => route('admin.advanced-search.download', ['filename' => $filename])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download exported file
     */
    public function downloadExport(string $filename): Response
    {
        $filepath = storage_path("app/exports/{$filename}");

        if (!file_exists($filepath)) {
            abort(404, 'File not found');
        }

        // Security check - ensure filename is safe
        if (!preg_match('/^search_export_[a-z]+_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.(csv|json)$/', $filename)) {
            abort(403, 'Invalid filename');
        }

        return response()->download($filepath)->deleteFileAfterSend();
    }

    /**
     * Get search analytics
     */
    public function getAnalytics(): JsonResponse
    {
        try {
            $analytics = $this->searchService->getSearchAnalytics();

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get search suggestions
     */
    public function getSuggestions(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1|max:255',
            'type' => 'required|string|in:missions,users,properties'
        ]);

        try {
            $query = $request->input('query');
            $type = $request->input('type');
            
            // Simple suggestion implementation
            $suggestions = [];
            
            switch ($type) {
                case 'missions':
                    $suggestions = \App\Models\Mission::where('title', 'LIKE', "%{$query}%")
                        ->orWhere('property_address', 'LIKE', "%{$query}%")
                        ->limit(10)
                        ->pluck('title')
                        ->unique()
                        ->values()
                        ->toArray();
                    break;
                    
                case 'users':
                    $suggestions = \App\Models\User::where('name', 'LIKE', "%{$query}%")
                        ->orWhere('email', 'LIKE', "%{$query}%")
                        ->limit(10)
                        ->pluck('name')
                        ->unique()
                        ->values()
                        ->toArray();
                    break;
                    
                case 'properties':
                    $suggestions = \App\Models\Property::where('property_address', 'LIKE', "%{$query}%")
                        ->orWhere('owner_name', 'LIKE', "%{$query}%")
                        ->limit(10)
                        ->pluck('property_address')
                        ->unique()
                        ->values()
                        ->toArray();
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $suggestions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get suggestions: ' . $e->getMessage()
            ], 500);
        }
    }
}