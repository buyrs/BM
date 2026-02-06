<?php

namespace App\Http\Controllers\Api;

use App\Models\QAReview;
use App\Models\Mission;
use App\Models\ChecklistItem;
use App\Services\PhotoVerificationService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QAController extends BaseApiController
{
    public function __construct(
        private PhotoVerificationService $verificationService,
        private AuditLogger $auditLogger
    ) {}

    /**
     * Get QA review queue.
     */
    public function queue(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$this->checkRole($request, ['admin'])) {
                return $this->forbidden('Insufficient permissions');
            }

            $paginationParams = $this->getPaginationParams($request);
            $filters = $this->getFilterParams($request, ['status', 'min_score', 'max_score']);

            $query = QAReview::with(['reviewable', 'reviewer'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            } else {
                $query->needsAttention();
            }

            if (isset($filters['min_score'])) {
                $query->where('score', '>=', $filters['min_score']);
            }

            if (isset($filters['max_score'])) {
                $query->where('score', '<=', $filters['max_score']);
            }

            $reviews = $query->paginate($paginationParams['per_page']);

            return $this->paginated($reviews, 'QA queue retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve QA queue');
        }
    }

    /**
     * Get QA dashboard statistics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$this->checkRole($request, ['admin'])) {
                return $this->forbidden('Insufficient permissions');
            }

            $stats = [
                'pending' => QAReview::pending()->count(),
                'flagged' => QAReview::where('status', QAReview::STATUS_FLAGGED)->count(),
                'approved_today' => QAReview::where('status', QAReview::STATUS_APPROVED)
                    ->whereDate('reviewed_at', today())
                    ->count(),
                'rejected_today' => QAReview::where('status', QAReview::STATUS_REJECTED)
                    ->whereDate('reviewed_at', today())
                    ->count(),
                'low_score_pending' => QAReview::pending()->lowScore(70)->count(),
                'avg_score_today' => round(QAReview::whereDate('created_at', today())->avg('score') ?? 0),
                'by_status' => QAReview::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status'),
            ];

            return $this->success($stats, 'Dashboard statistics retrieved');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve dashboard statistics');
        }
    }

    /**
     * Verify a specific mission's photos.
     */
    public function verifyMission(Request $request, int $missionId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$this->checkRole($request, ['admin', 'ops'])) {
                return $this->forbidden('Insufficient permissions');
            }

            $mission = Mission::with(['checklists.items', 'property'])->findOrFail($missionId);
            
            $verification = $this->verificationService->verifyMissionPhotos($mission);

            // Create or update QA review record
            $review = QAReview::updateOrCreate(
                [
                    'reviewable_type' => Mission::class,
                    'reviewable_id' => $mission->id,
                ],
                [
                    'score' => $verification['overall_score'],
                    'verification_data' => $verification,
                    'status' => $this->determineInitialStatus($verification['overall_score']),
                ]
            );

            return $this->success([
                'verification' => $verification,
                'review' => $review,
            ], 'Mission photos verified');

        } catch (\Exception $e) {
            return $this->serverError('Failed to verify mission photos');
        }
    }

    /**
     * Approve a QA review.
     */
    public function approve(Request $request, int $reviewId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$this->checkRole($request, ['admin'])) {
                return $this->forbidden('Insufficient permissions');
            }

            $review = QAReview::findOrFail($reviewId);
            
            $validated = $request->validate([
                'notes' => ['nullable', 'string', 'max:1000'],
            ]);

            $review->approve($user->id, $validated['notes'] ?? null);

            $this->auditLogger->log('qa_review_approved', $user, [
                'review_id' => $review->id,
                'reviewable_type' => $review->reviewable_type,
                'reviewable_id' => $review->reviewable_id,
            ]);

            return $this->success($review->fresh(), 'Review approved');

        } catch (\Exception $e) {
            return $this->serverError('Failed to approve review');
        }
    }

    /**
     * Reject a QA review.
     */
    public function reject(Request $request, int $reviewId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$this->checkRole($request, ['admin'])) {
                return $this->forbidden('Insufficient permissions');
            }

            $review = QAReview::findOrFail($reviewId);
            
            $validated = $request->validate([
                'reason' => ['required', 'string', 'max:1000'],
            ]);

            $review->reject($user->id, $validated['reason']);

            $this->auditLogger->log('qa_review_rejected', $user, [
                'review_id' => $review->id,
                'reason' => $validated['reason'],
            ]);

            return $this->success($review->fresh(), 'Review rejected');

        } catch (\Exception $e) {
            return $this->serverError('Failed to reject review');
        }
    }

    /**
     * Flag a review for investigation.
     */
    public function flag(Request $request, int $reviewId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$this->checkRole($request, ['admin'])) {
                return $this->forbidden('Insufficient permissions');
            }

            $review = QAReview::findOrFail($reviewId);
            
            $validated = $request->validate([
                'reason' => ['required', 'string', 'max:1000'],
            ]);

            $review->flag($user->id, $validated['reason']);

            $this->auditLogger->log('qa_review_flagged', $user, [
                'review_id' => $review->id,
                'reason' => $validated['reason'],
            ]);

            return $this->success($review->fresh(), 'Review flagged for investigation');

        } catch (\Exception $e) {
            return $this->serverError('Failed to flag review');
        }
    }

    /**
     * Bulk approve reviews.
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            if (!$this->checkRole($request, ['admin'])) {
                return $this->forbidden('Insufficient permissions');
            }

            $validated = $request->validate([
                'review_ids' => ['required', 'array'],
                'review_ids.*' => ['exists:qa_reviews,id'],
            ]);

            $approved = 0;
            foreach ($validated['review_ids'] as $reviewId) {
                $review = QAReview::find($reviewId);
                if ($review && $review->status === QAReview::STATUS_PENDING) {
                    $review->approve($user->id);
                    $approved++;
                }
            }

            $this->auditLogger->log('qa_bulk_approve', $user, [
                'count' => $approved,
                'review_ids' => $validated['review_ids'],
            ]);

            return $this->success([
                'approved_count' => $approved,
            ], "{$approved} reviews approved");

        } catch (\Exception $e) {
            return $this->serverError('Failed to bulk approve reviews');
        }
    }

    /**
     * Determine initial status based on score.
     */
    private function determineInitialStatus(int $score): string
    {
        if ($score >= 90) {
            return QAReview::STATUS_PENDING; // Auto-approve candidates
        } elseif ($score >= 70) {
            return QAReview::STATUS_PENDING;
        } else {
            return QAReview::STATUS_FLAGGED;
        }
    }
}
