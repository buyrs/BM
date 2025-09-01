<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Notification;
use App\Models\User;
use App\Models\BailMobilite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $fillable = [
            'type', 'recipient_id', 'bail_mobilite_id', 'scheduled_at',
            'sent_at', 'status', 'data'
        ];

        $notification = new Notification();
        $this->assertEquals($fillable, $notification->getFillable());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $data = ['key' => 'value', 'number' => 123];
        $notification = Notification::factory()->create([
            'scheduled_at' => '2025-01-15 10:30:00',
            'sent_at' => '2025-01-15 11:00:00',
            'data' => $data
        ]);

        $this->assertInstanceOf(Carbon::class, $notification->scheduled_at);
        $this->assertInstanceOf(Carbon::class, $notification->sent_at);
        $this->assertIsArray($notification->data);
        $this->assertEquals($data, $notification->data);
    }

    /** @test */
    public function it_belongs_to_recipient()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['recipient_id' => $user->id]);

        $this->assertInstanceOf(User::class, $notification->recipient);
        $this->assertEquals($user->id, $notification->recipient->id);
    }

    /** @test */
    public function it_belongs_to_bail_mobilite()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $notification = Notification::factory()->create(['bail_mobilite_id' => $bailMobilite->id]);

        $this->assertInstanceOf(BailMobilite::class, $notification->bailMobilite);
        $this->assertEquals($bailMobilite->id, $notification->bailMobilite->id);
    }

    /** @test */
    public function it_can_have_null_bail_mobilite()
    {
        $notification = Notification::factory()->create(['bail_mobilite_id' => null]);

        $this->assertNull($notification->bailMobilite);
    }

    /** @test */
    public function it_validates_status_enum()
    {
        $validStatuses = ['pending', 'sent', 'cancelled'];

        foreach ($validStatuses as $status) {
            $notification = Notification::factory()->create(['status' => $status]);
            $this->assertEquals($status, $notification->status);
        }
    }

    /** @test */
    public function it_has_default_status_pending()
    {
        $notification = Notification::factory()->create(['status' => null]);
        $this->assertEquals('pending', $notification->fresh()->status);
    }

    /** @test */
    public function it_can_store_notification_data()
    {
        $data = [
            'message' => 'Test notification',
            'bail_mobilite_id' => 123,
            'checker_name' => 'John Doe'
        ];

        $notification = Notification::factory()->create(['data' => $data]);

        $this->assertEquals($data, $notification->data);
        $this->assertEquals('Test notification', $notification->data['message']);
        $this->assertEquals(123, $notification->data['bail_mobilite_id']);
        $this->assertEquals('John Doe', $notification->data['checker_name']);
    }

    /** @test */
    public function it_can_be_marked_as_sent()
    {
        $notification = Notification::factory()->create([
            'status' => 'pending',
            'sent_at' => null
        ]);

        $sentAt = now();
        $notification->update([
            'status' => 'sent',
            'sent_at' => $sentAt
        ]);

        $this->assertEquals('sent', $notification->status);
        $this->assertEquals($sentAt->format('Y-m-d H:i:s'), $notification->sent_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_be_cancelled()
    {
        $notification = Notification::factory()->create(['status' => 'pending']);

        $notification->update(['status' => 'cancelled']);

        $this->assertEquals('cancelled', $notification->status);
    }

    /** @test */
    public function it_can_be_scheduled_for_future()
    {
        $futureDate = now()->addDays(10);
        $notification = Notification::factory()->create([
            'scheduled_at' => $futureDate,
            'status' => 'pending'
        ]);

        $this->assertTrue($notification->scheduled_at->isFuture());
        $this->assertEquals('pending', $notification->status);
    }
}