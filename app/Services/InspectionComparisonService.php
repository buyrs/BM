<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Property;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InspectionComparisonService extends BaseService
{
    /**
     * Get comparison data between current and previous inspection.
     */
    public function getComparison(Mission $currentMission): array
    {
        $previousMission = $this->findPreviousMission($currentMission);

        if (!$previousMission) {
            return [
                'has_previous' => false,
                'current' => $this->formatMissionData($currentMission),
                'previous' => null,
                'changes' => [],
            ];
        }

        $currentData = $this->formatMissionData($currentMission);
        $previousData = $this->formatMissionData($previousMission);
        $changes = $this->calculateChanges($currentData, $previousData);

        return [
            'has_previous' => true,
            'current' => $currentData,
            'previous' => $previousData,
            'changes' => $changes,
            'summary' => $this->generateSummary($changes),
        ];
    }

    /**
     * Find the previous mission for the same property.
     */
    public function findPreviousMission(Mission $currentMission): ?Mission
    {
        return Mission::where('property_address', $currentMission->property_address)
            ->where('id', '!=', $currentMission->id)
            ->where('status', 'completed')
            ->where('created_at', '<', $currentMission->created_at)
            ->orderBy('created_at', 'desc')
            ->with(['checklists.items', 'checker'])
            ->first();
    }

    /**
     * Get photo comparison for a specific area/room.
     */
    public function getPhotoComparison(Mission $currentMission, string $areaName): array
    {
        $previousMission = $this->findPreviousMission($currentMission);

        $currentPhotos = $this->getPhotosForArea($currentMission, $areaName);
        $previousPhotos = $previousMission 
            ? $this->getPhotosForArea($previousMission, $areaName) 
            : [];

        return [
            'area' => $areaName,
            'current' => $currentPhotos,
            'previous' => $previousPhotos,
            'changes' => $this->detectPhotoChanges($currentPhotos, $previousPhotos),
        ];
    }

    /**
     * Get all areas with comparison status.
     */
    public function getAreasWithStatus(Mission $currentMission): Collection
    {
        $previousMission = $this->findPreviousMission($currentMission);
        
        $currentAreas = $this->getAreasFromMission($currentMission);
        $previousAreas = $previousMission 
            ? $this->getAreasFromMission($previousMission) 
            : collect();

        return $currentAreas->map(function ($area) use ($previousAreas) {
            $previousArea = $previousAreas->firstWhere('name', $area['name']);
            
            $status = 'new';
            if ($previousArea) {
                $status = $this->determineAreaStatus($area, $previousArea);
            }

            return array_merge($area, [
                'status' => $status,
                'previous_condition' => $previousArea['condition'] ?? null,
                'has_previous' => $previousArea !== null,
            ]);
        });
    }

    /**
     * Format mission data for comparison.
     */
    private function formatMissionData(Mission $mission): array
    {
        $checklists = $mission->checklists()->with('items')->get();
        
        return [
            'id' => $mission->id,
            'title' => $mission->title,
            'status' => $mission->status,
            'completed_at' => $mission->completed_at ?? $mission->updated_at,
            'checker' => [
                'id' => $mission->checker?->id,
                'name' => $mission->checker?->name,
            ],
            'areas' => $this->getAreasFromMission($mission)->toArray(),
            'total_items' => $checklists->sum(fn($c) => $c->items->count()),
            'completed_items' => $checklists->sum(fn($c) => $c->items->where('completed', true)->count()),
            'photo_count' => $this->countPhotos($mission),
            'issues' => $this->getIssues($mission),
        ];
    }

    /**
     * Get areas from mission checklists.
     */
    private function getAreasFromMission(Mission $mission): Collection
    {
        $checklists = $mission->checklists()->with('items')->get();
        
        return $checklists->map(function ($checklist) {
            $items = $checklist->items;
            $completedItems = $items->where('completed', true);
            
            $condition = $this->calculateCondition($items);
            
            return [
                'id' => $checklist->id,
                'name' => $checklist->name,
                'item_count' => $items->count(),
                'completed_count' => $completedItems->count(),
                'condition' => $condition,
                'condition_score' => $this->calculateConditionScore($items),
                'photos' => $this->getPhotosForChecklist($checklist),
                'notes' => $checklist->notes ?? '',
            ];
        });
    }

    /**
     * Calculate overall condition for an area.
     */
    private function calculateCondition(Collection $items): string
    {
        if ($items->isEmpty()) {
            return 'unknown';
        }

        $issueCount = $items->filter(function ($item) {
            return isset($item->data['has_issue']) && $item->data['has_issue'];
        })->count();

        $ratio = $issueCount / $items->count();

        if ($ratio === 0) return 'excellent';
        if ($ratio < 0.1) return 'good';
        if ($ratio < 0.3) return 'fair';
        if ($ratio < 0.5) return 'poor';
        return 'critical';
    }

    /**
     * Calculate numeric condition score (1-10).
     */
    private function calculateConditionScore(Collection $items): float
    {
        if ($items->isEmpty()) {
            return 5.0;
        }

        $scores = $items->map(function ($item) {
            if (isset($item->data['condition_score'])) {
                return (float) $item->data['condition_score'];
            }
            // Default scoring based on completion and issues
            $score = 7.0;
            if (isset($item->data['has_issue']) && $item->data['has_issue']) {
                $score -= 3.0;
            }
            if ($item->completed) {
                $score += 1.0;
            }
            return max(1, min(10, $score));
        });

        return round($scores->average(), 1);
    }

    /**
     * Get photos for a specific area.
     */
    private function getPhotosForArea(Mission $mission, string $areaName): array
    {
        $checklist = $mission->checklists()->where('name', $areaName)->first();
        
        if (!$checklist) {
            return [];
        }

        return $this->getPhotosForChecklist($checklist);
    }

    /**
     * Get photos for a checklist.
     */
    private function getPhotosForChecklist(Checklist $checklist): array
    {
        $photos = [];

        foreach ($checklist->items as $item) {
            if (isset($item->data['photos']) && is_array($item->data['photos'])) {
                foreach ($item->data['photos'] as $photo) {
                    $photos[] = [
                        'url' => $photo['url'] ?? $photo,
                        'item_name' => $item->name,
                        'taken_at' => $photo['taken_at'] ?? null,
                        'caption' => $photo['caption'] ?? '',
                    ];
                }
            }
        }

        return $photos;
    }

    /**
     * Count total photos in a mission.
     */
    private function countPhotos(Mission $mission): int
    {
        $count = 0;

        foreach ($mission->checklists as $checklist) {
            foreach ($checklist->items as $item) {
                if (isset($item->data['photos']) && is_array($item->data['photos'])) {
                    $count += count($item->data['photos']);
                }
            }
        }

        return $count;
    }

    /**
     * Get issues from a mission.
     */
    private function getIssues(Mission $mission): array
    {
        $issues = [];

        foreach ($mission->checklists as $checklist) {
            foreach ($checklist->items as $item) {
                if (isset($item->data['has_issue']) && $item->data['has_issue']) {
                    $issues[] = [
                        'area' => $checklist->name,
                        'item' => $item->name,
                        'description' => $item->data['issue_description'] ?? '',
                        'severity' => $item->data['severity'] ?? 'medium',
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Calculate changes between inspections.
     */
    private function calculateChanges(array $current, array $previous): array
    {
        $changes = [
            'improved' => [],
            'declined' => [],
            'new_issues' => [],
            'resolved_issues' => [],
            'unchanged' => [],
        ];

        $currentAreas = collect($current['areas']);
        $previousAreas = collect($previous['areas']);

        foreach ($currentAreas as $currentArea) {
            $previousArea = $previousAreas->firstWhere('name', $currentArea['name']);

            if (!$previousArea) {
                $changes['unchanged'][] = [
                    'area' => $currentArea['name'],
                    'note' => 'New area in inspection',
                ];
                continue;
            }

            $scoreDiff = $currentArea['condition_score'] - $previousArea['condition_score'];

            if ($scoreDiff > 0.5) {
                $changes['improved'][] = [
                    'area' => $currentArea['name'],
                    'from' => $previousArea['condition'],
                    'to' => $currentArea['condition'],
                    'score_change' => round($scoreDiff, 1),
                ];
            } elseif ($scoreDiff < -0.5) {
                $changes['declined'][] = [
                    'area' => $currentArea['name'],
                    'from' => $previousArea['condition'],
                    'to' => $currentArea['condition'],
                    'score_change' => round($scoreDiff, 1),
                ];
            } else {
                $changes['unchanged'][] = [
                    'area' => $currentArea['name'],
                    'condition' => $currentArea['condition'],
                ];
            }
        }

        // Compare issues
        $currentIssueKeys = collect($current['issues'])->pluck('item')->toArray();
        $previousIssueKeys = collect($previous['issues'])->pluck('item')->toArray();

        foreach ($current['issues'] as $issue) {
            if (!in_array($issue['item'], $previousIssueKeys)) {
                $changes['new_issues'][] = $issue;
            }
        }

        foreach ($previous['issues'] as $issue) {
            if (!in_array($issue['item'], $currentIssueKeys)) {
                $changes['resolved_issues'][] = $issue;
            }
        }

        return $changes;
    }

    /**
     * Detect photo changes between inspections.
     */
    private function detectPhotoChanges(array $current, array $previous): array
    {
        return [
            'new_photos' => count($current) - count($previous),
            'has_changes' => count($current) !== count($previous),
        ];
    }

    /**
     * Determine area status based on comparison.
     */
    private function determineAreaStatus(array $current, array $previous): string
    {
        $scoreDiff = $current['condition_score'] - $previous['condition_score'];

        if ($scoreDiff > 0.5) return 'improved';
        if ($scoreDiff < -0.5) return 'declined';
        return 'unchanged';
    }

    /**
     * Generate a summary of the comparison.
     */
    private function generateSummary(array $changes): array
    {
        return [
            'improved_count' => count($changes['improved']),
            'declined_count' => count($changes['declined']),
            'new_issues_count' => count($changes['new_issues']),
            'resolved_issues_count' => count($changes['resolved_issues']),
            'overall_trend' => $this->calculateOverallTrend($changes),
        ];
    }

    /**
     * Calculate overall trend.
     */
    private function calculateOverallTrend(array $changes): string
    {
        $score = count($changes['improved']) - count($changes['declined'])
               + count($changes['resolved_issues']) - count($changes['new_issues']);

        if ($score > 0) return 'improving';
        if ($score < 0) return 'declining';
        return 'stable';
    }
}
