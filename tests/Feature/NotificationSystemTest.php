<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\BailMobilite;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Notifications\BailMobiliteExitReminder;
use App\Notifications\ChecklistValidationNotification;
use App\Notifications\IncidentAlertNotification;
use App\Notifications\MissionAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Spatie\Permission\Models\Role;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $notificationService;
    protected User $opsUser;
    protected User $checker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'ops']);
        Role::create(['name' => 'checker']);
        
        // Create users
        $this->opsUser = User::factory()->create();
        $this->opsUser->assignRole('ops');
        
        $this->checker = User::factory()->create();
        $this->checker->assignRole('checker');
        
        $this->notificationService = new NotificationService();
        
        // Fake mail and notifications
        Mail::fake();
        Queue::fake();
        NotificationFacade::fake();
    }

    /** @test */
    public function it_schedules_exit_reminder_notifications_correctly()
    {
        $bailMobilite = BailMobilite::factory()->create([
            'end_date' => now()->addDays(15),
            'status' => 'in_progress'
        ]);

        $this->notificationService->scheduleExitReminder($bailMobilite, $this->opsUser);

        // Check database record
        $this->assertDatabaseHas('notifications', [
            'type' => 'exit_reminder',
            'recipient_id' => $this->opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'pending'
        ]);

        $notification = Notification::where('type', 'exit_reminder')->first();
        $expectedDate = $bailMobilite->end_date->subDays(10);
        $this->assertEquals($expectedDate->format('Y-m-d'), $notification->scheduled_at->format('Y-m-d'));
        
        // Test data structure
        $this->assertArrayHasKey('bail_mobilite_id', $notification->data);
        $this->assertArrayHasKey('days_remaining', $notification->data);
        $this->assertEquals($bailMobilite->id, $notification->data['bail_mobilite_id']);
        $this->assertEquals(10, $notification->data['days_remaining']);
    }

    /** @test */
    public function it_processes_scheduled_notifications_when_due()
    {
        $bailMobilite = BailMobilite::factory()->create();
        
        // Create a notification scheduled for now
        $notification = Notification::create([
            'type' => 'exit_reminder',
            'recipient_id' => $this->opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => now()->subMinute(),
            'status' => 'pending',
            'data' => [
                'bail_mobilite_id' => $bailMobilite->id,
                'days_remaining' => 10
            ]
        ]);

        $this->notificationService->processScheduledNotifications();

        $notification->refresh();
        $this->assertEquals('sent', $notification->status);
        $this->assertNotNull($notification->sent_at);

        // Check that Laravel notification was sent
        NotificationFacade::assertSentTo(
            $this->opsUser,
            BailMobiliteExitReminder::class,
            function ($notification, $channels) use ($bailMobilite) {
                return $notification->bailMobilite->id === $bailMobilite->id;
            }
        );
    }

    /** @test */
    public function it_does_not_process_future_scheduled_notifications()
    {
        $bailMobilite = BailMobilite::factory()->create();
        
        // Create a notification scheduled for future
        $notification = Notification::create([
            'type' => 'exit_reminder',
            'recipient_id' => $this->opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => now()->addHour(),
            'status' => 'pending',
            'data' => ['bail_mobilite_id' => $bailMobilite->id]
        ]);

        $this->notificationService->processScheduledNotifications();

        $notification->refresh();
        $this->assertEquals('pending', $notification->status);
        $this->assertNull($notification->sent_at);

        // Check that no Laravel notification was sent
        NotificationFacade::assertNothingSent();
    }

    /** @test */
    public function it_sends_ops_alerts_immediately()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $data = ['message' => 'Checklist needs validation', 'mission_type' => 'entry'];

        $this->notificationService->sendOpsAlert(
            $this->opsUser,
            'checklist_validation',
            $bailMobilite,
            $data
        );

        // Check database record
        $this->assertDatabaseHas('notifications', [
            'type' => 'checklist_validation',
            'recipient_id' => $this->opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'sent'
        ]);

        $notification = Notification::where('type', 'checklist_validation')->first();
        $this->assertNotNull($notification->sent_at);
        $this->assertEquals($data, $notification->data);

        // Check that Laravel notification was sent
        NotificationFacade::assertSentTo(
            $this->opsUser,
            ChecklistValidationNotification::class
        );
    }

    /** @test */
    public function it_sends_incident_alerts_to_ops()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'incident']);
        $data = [
            'incident_type' => 'keys_not_returned',
            'description' => 'Tenant did not return keys'
        ];

        $this->notificationService->sendOpsAlert(
            $this->opsUser,
            'incident_alert',
            $bailMobilite,
            $data
        );

        // Check database record
        $this->assertDatabaseHas('notifications', [
            'type' => 'incident_alert',
            'recipient_id' => $this->opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'sent'
        ]);

        // Check that Laravel notification was sent
        NotificationFacade::assertSentTo(
            $this->opsUser,
            IncidentAlertNotification::class,
            function ($notification, $channels) use ($data) {
                return $notification->data['incident_type'] === $data['incident_type'];
            }
        );
    }

    /** @test */
    public function it_notifies_checkers_of_mission_assignments()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $data = [
            'mission_type' => 'entry',
            'scheduled_time' => '14:00',
            'address' => $bailMobilite->address
        ];

        $this->notificationService->notifyChecker($this->checker, $bailMobilite, $data);

        // Check database record
        $this->assertDatabaseHas('notifications', [
            'type' => 'mission_assigned',
            'recipient_id' => $this->checker->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'sent'
        ]);

        // Check that Laravel notification was sent
        NotificationFacade::assertSentTo(
            $this->checker,
            MissionAssignedNotification::class,
            function ($notification, $channels) use ($data) {
                return $notification->data['mission_type'] === $data['mission_type'];
            }
        );
    }

    /** @test */
    public function it_cancels_scheduled_notifications_correctly()
    {
        $bailMobilite = BailMobilite::factory()->create();

        // Create multiple notifications for the bail mobilité
        $pendingNotification1 = Notification::create([
            'type' => 'exit_reminder',
            'recipient_id' => $this->opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => now()->addDays(5),
            'status' => 'pending',
            'data' => ['bail_mobilite_id' => $bailMobilite->id]
        ]);

        $pendingNotification2 = Notification::create([
            'type' => 'checklist_validation',
            'recipient_id' => $this->opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => now()->addDays(3),
            'status' => 'pending',
            'data' => ['bail_mobilite_id' => $bailMobilite->id]
        ]);

        $sentNotification = Notification::create([
            'type' => 'mission_assigned',
            'recipient_id' => $this->checker->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'sent_at' => now()->subHour(),
            'status' => 'sent',
            'data' => ['bail_mobilite_id' => $bailMobilite->id]
        ]);

        $this->notificationService->cancelScheduledNotifications($bailMobilite);

        // Pending notifications should be cancelled
        $pendingNotification1->refresh();
        $pendingNotification2->refresh();
        $this->assertEquals('cancelled', $pendingNotification1->status);
        $this->assertEquals('cancelled', $pendingNotification2->status);

        // Sent notifications should remain unchanged
        $sentNotification->refresh();
        $this->assertEquals('sent', $sentNotification->status);
    }

    /** @test */
    public function it_gets_pending_notifications_for_user()
    {
        $otherUser = User::factory()->create();
        $bailMobilite = BailMobilite::factory()->create();

        // Create notifications for the ops user
        $pendingNotification = Notification::create([
            'type' => 'exit_reminder',
            'recipient_id' => $this->opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'pending',
            'data' => ['message' => 'Pending notification']
        ]);

        $sentNotification = Notification::create([
            'type' => 'checklist_validation',
            'recipient_id' => $this->opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'sent',
            'data' => ['message' => 'Sent notification']
        ]);

        // Create notification for other user
        $otherUserNotification = Notification::create([
            'type' => 'mission_assigned',
            'recipient_id' => $otherUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'pending',
            'data' => ['message' => 'Other user notification']
        ]);

        $pendingNotifications = $this->notificationService->getPendingNotificationsForUser($this->opsUser);

        $this->assertCount(1, $pendingNotifications);
        $this->assertTrue($pendingNotifications->contains($pendingNotification));
        $this->assertFalse($pendingNotifications->contains($sentNotification));
        $this->assertFalse($pendingNotifications->contains($otherUserNotification));
    }

    /** @test */
    public function it_marks_notifications_as_read()
    {
        $notification = Notification::create([
            'type' => 'exit_reminder',
            'recipient_id' => $this->opsUser->id,
            'status' => 'sent',
            'data' => ['read' => false, 'message' => 'Test notification']
        ]);

        $this->notificationService->markAsRead($notification);

        $notification->refresh();
        $this->assertTrue($notification->data['read']);
    }

    /** @test */
    public function it_handles_notification_channels_correctly()
    {
        $bailMobilite = BailMobilite::factory()->create();

        // Test that exit reminders use both mail and database channels
        $this->notificationService->scheduleExitReminder($bailMobilite, $this->opsUser);
        
        $notification = Notification::where('type', 'exit_reminder')->first();
        $notification->update(['scheduled_at' => now()->subMinute()]);
        
        $this->notificationService->processScheduledNotifications();

        NotificationFacade::assertSentTo(
            $this->opsUser,
            BailMobiliteExitReminder::class,
            function ($notification, $channels) {
                return in_array('mail', $channels) && in_array('database', $channels);
            }
        );
    }

    /** @test */
    public function it_handles_notification_failures_gracefully()
    {
        $bailMobilite = BailMobilite::factory()->create();
        
        // Create a notification with invalid recipient
        $invalidNotification = Notification::create([
            'type' => 'exit_reminder',
            'recipient_id' => 99999, // Non-existent user
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => now()->subMinute(),
            'status' => 'pending',
            'data' => ['bail_mobilite_id' => $bailMobilite->id]
        ]);

        // Processing should not throw an exception
        $this->notificationService->processScheduledNotifications();

        // The notification should remain pending or be marked as failed
        $invalidNotification->refresh();
        $this->assertIn($invalidNotification->status, ['pending', 'failed']);
    }

    /** @test */
    public function it_respects_notification_preferences()
    {
        // This test would be expanded if user notification preferences were implemented
        $bailMobilite = BailMobilite::factory()->create();
        
        // For now, just test that notifications are sent regardless
        $this->notificationService->sendOpsAlert(
            $this->opsUser,
            'checklist_validation',
            $bailMobilite,
            ['message' => 'Test notification']
        );

        NotificationFacade::assertSentTo($this->opsUser, ChecklistValidationNotification::class);
    }

    /** @test */
    public function it_batches_multiple_notifications_efficiently()
    {
        $bailMobilites = BailMobilite::factory()->count(5)->create([
            'end_date' => now()->addDays(15)
        ]);

        // Schedule multiple exit reminders
        foreach ($bailMobilites as $bailMobilite) {
            $this->notificationService->scheduleExitReminder($bailMobilite, $this->opsUser);
        }

        // Update all to be due now
        Notification::where('type', 'exit_reminder')->update([
            'scheduled_at' => now()->subMinute()
        ]);

        $this->notificationService->processScheduledNotifications();

        // All notifications should be processed
        $this->assertEquals(5, Notification::where('status', 'sent')->count());
        
        // All should have been sent to the ops user
        NotificationFacade::assertSentToTimes($this->opsUser, BailMobiliteExitReminder::class, 5);
    }
}