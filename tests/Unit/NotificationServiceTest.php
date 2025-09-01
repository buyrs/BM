<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\NotificationService;
use App\Models\BailMobilite;
use App\Models\User;
use App\Models\Notification;
use App\Notifications\BailMobiliteExitReminder;
use App\Notifications\ChecklistValidationNotification;
use App\Notifications\IncidentAlertNotification;
use App\Notifications\MissionAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = new NotificationService();
        Mail::fake();
        Queue::fake();
    }

    /** @test */
    public function it_can_schedule_exit_reminder()
    {
        $bailMobilite = BailMobilite::factory()->create([
            'end_date' => now()->addDays(15)
        ]);
        $opsUser = User::factory()->create();

        $this->notificationService->scheduleExitReminder($bailMobilite, $opsUser);

        $this->assertDatabaseHas('notifications', [
            'type' => 'EXIT_REMINDER',
            'recipient_id' => $opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'pending'
        ]);

        $notification = Notification::where('type', 'EXIT_REMINDER')->first();
        $expectedDate = $bailMobilite->end_date->subDays(10);
        $this->assertEquals($expectedDate->format('Y-m-d'), $notification->scheduled_at->format('Y-m-d'));
    }

    /** @test */
    public function it_can_send_ops_alert_for_checklist_validation()
    {
        $opsUser = User::factory()->create();
        $bailMobilite = BailMobilite::factory()->create();
        $data = ['message' => 'Checklist needs validation'];

        $this->notificationService->sendOpsAlert($opsUser, 'CHECKLIST_VALIDATION', $bailMobilite, $data);

        $this->assertDatabaseHas('notifications', [
            'type' => 'CHECKLIST_VALIDATION',
            'recipient_id' => $opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'sent'
        ]);

        $notification = Notification::where('type', 'CHECKLIST_VALIDATION')->first();
        $this->assertEquals($data, $notification->data);
        $this->assertNotNull($notification->sent_at);
    }

    /** @test */
    public function it_can_send_ops_alert_for_incident()
    {
        $opsUser = User::factory()->create();
        $bailMobilite = BailMobilite::factory()->create();
        $data = ['incident_type' => 'keys_not_returned'];

        $this->notificationService->sendOpsAlert($opsUser, 'INCIDENT_ALERT', $bailMobilite, $data);

        $this->assertDatabaseHas('notifications', [
            'type' => 'INCIDENT_ALERT',
            'recipient_id' => $opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'sent'
        ]);
    }

    /** @test */
    public function it_can_notify_checker_of_assignment()
    {
        $checker = User::factory()->create();
        $bailMobilite = BailMobilite::factory()->create();
        $data = ['mission_type' => 'entry', 'scheduled_time' => '14:00'];

        $this->notificationService->notifyChecker($checker, $bailMobilite, $data);

        $this->assertDatabaseHas('notifications', [
            'type' => 'MISSION_ASSIGNED',
            'recipient_id' => $checker->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'sent'
        ]);

        $notification = Notification::where('type', 'MISSION_ASSIGNED')->first();
        $this->assertEquals($data, $notification->data);
    }

    /** @test */
    public function it_can_cancel_scheduled_notifications()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $opsUser = User::factory()->create();

        // Create some scheduled notifications
        $notification1 = Notification::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'pending',
            'type' => 'EXIT_REMINDER'
        ]);
        $notification2 = Notification::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'pending',
            'type' => 'CHECKLIST_VALIDATION'
        ]);
        $sentNotification = Notification::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'sent',
            'type' => 'MISSION_ASSIGNED'
        ]);

        $this->notificationService->cancelScheduledNotifications($bailMobilite);

        $this->assertEquals('cancelled', $notification1->fresh()->status);
        $this->assertEquals('cancelled', $notification2->fresh()->status);
        $this->assertEquals('sent', $sentNotification->fresh()->status); // Should not be cancelled
    }

    /** @test */
    public function it_can_process_scheduled_notifications()
    {
        $opsUser = User::factory()->create();
        $bailMobilite = BailMobilite::factory()->create();

        // Create a notification scheduled for now
        $notification = Notification::factory()->create([
            'type' => 'EXIT_REMINDER',
            'recipient_id' => $opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => now()->subMinute(),
            'status' => 'pending'
        ]);

        $this->notificationService->processScheduledNotifications();

        $this->assertEquals('sent', $notification->fresh()->status);
        $this->assertNotNull($notification->fresh()->sent_at);
    }

    /** @test */
    public function it_does_not_process_future_notifications()
    {
        $opsUser = User::factory()->create();
        $bailMobilite = BailMobilite::factory()->create();

        // Create a notification scheduled for future
        $notification = Notification::factory()->create([
            'type' => 'EXIT_REMINDER',
            'recipient_id' => $opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => now()->addHour(),
            'status' => 'pending'
        ]);

        $this->notificationService->processScheduledNotifications();

        $this->assertEquals('pending', $notification->fresh()->status);
        $this->assertNull($notification->fresh()->sent_at);
    }

    /** @test */
    public function it_can_get_pending_notifications_for_user()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $pendingNotification = Notification::factory()->create([
            'recipient_id' => $user->id,
            'status' => 'pending'
        ]);
        $sentNotification = Notification::factory()->create([
            'recipient_id' => $user->id,
            'status' => 'sent'
        ]);
        $otherUserNotification = Notification::factory()->create([
            'recipient_id' => $otherUser->id,
            'status' => 'pending'
        ]);

        $pendingNotifications = $this->notificationService->getPendingNotificationsForUser($user);

        $this->assertCount(1, $pendingNotifications);
        $this->assertTrue($pendingNotifications->contains($pendingNotification));
        $this->assertFalse($pendingNotifications->contains($sentNotification));
        $this->assertFalse($pendingNotifications->contains($otherUserNotification));
    }

    /** @test */
    public function it_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'status' => 'sent',
            'data' => ['read' => false]
        ]);

        $this->notificationService->markAsRead($notification);

        $this->assertTrue($notification->fresh()->data['read']);
    }
}