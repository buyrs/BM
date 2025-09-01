<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\BailMobilite;
use App\Models\User;
use App\Models\Mission;
use App\Models\Notification;
use App\Models\BailMobiliteSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;


class BailMobiliteTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $fillable = [
            'start_date', 'end_date', 'address', 'tenant_name',
            'tenant_phone', 'tenant_email', 'notes', 'status', 'ops_user_id',
            'entry_mission_id', 'exit_mission_id'
        ];

        $bailMobilite = new BailMobilite();
        $this->assertEquals($fillable, $bailMobilite->getFillable());
    }

    /** @test */
    public function it_casts_dates_correctly()
    {
        $bailMobilite = BailMobilite::factory()->create([
            'start_date' => '2025-01-15',
            'end_date' => '2025-02-15'
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $bailMobilite->start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $bailMobilite->end_date);
    }

    /** @test */
    public function it_belongs_to_ops_user()
    {
        $user = User::factory()->create();
        $bailMobilite = BailMobilite::factory()->create(['ops_user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $bailMobilite->opsUser);
        $this->assertEquals($user->id, $bailMobilite->opsUser->id);
    }

    /** @test */
    public function it_belongs_to_entry_mission()
    {
        $mission = Mission::factory()->create();
        $bailMobilite = BailMobilite::factory()->create(['entry_mission_id' => $mission->id]);

        $this->assertInstanceOf(Mission::class, $bailMobilite->entryMission);
        $this->assertEquals($mission->id, $bailMobilite->entryMission->id);
    }

    /** @test */
    public function it_belongs_to_exit_mission()
    {
        $mission = Mission::factory()->create();
        $bailMobilite = BailMobilite::factory()->create(['exit_mission_id' => $mission->id]);

        $this->assertInstanceOf(Mission::class, $bailMobilite->exitMission);
        $this->assertEquals($mission->id, $bailMobilite->exitMission->id);
    }

    /** @test */
    public function it_has_many_notifications()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $notification = Notification::factory()->create(['bail_mobilite_id' => $bailMobilite->id]);

        $this->assertTrue($bailMobilite->notifications->contains($notification));
    }

    /** @test */
    public function it_has_many_signatures()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $signature = BailMobiliteSignature::factory()->create(['bail_mobilite_id' => $bailMobilite->id]);

        $this->assertTrue($bailMobilite->signatures->contains($signature));
    }

    /** @test */
    public function it_has_entry_signature_relation()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $entrySignature = BailMobiliteSignature::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'entry'
        ]);
        $exitSignature = BailMobiliteSignature::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'exit'
        ]);

        $this->assertEquals($entrySignature->id, $bailMobilite->entrySignature->id);
        $this->assertNotEquals($exitSignature->id, $bailMobilite->entrySignature->id);
    }

    /** @test */
    public function it_has_exit_signature_relation()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $entrySignature = BailMobiliteSignature::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'entry'
        ]);
        $exitSignature = BailMobiliteSignature::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'exit'
        ]);

        $this->assertEquals($exitSignature->id, $bailMobilite->exitSignature->id);
        $this->assertNotEquals($entrySignature->id, $bailMobilite->exitSignature->id);
    }

    /** @test */
    public function it_has_assigned_scope()
    {
        $assignedBM = BailMobilite::factory()->create(['status' => 'assigned']);
        $inProgressBM = BailMobilite::factory()->create(['status' => 'in_progress']);

        $assigned = BailMobilite::assigned()->get();

        $this->assertTrue($assigned->contains($assignedBM));
        $this->assertFalse($assigned->contains($inProgressBM));
    }

    /** @test */
    public function it_has_in_progress_scope()
    {
        $assignedBM = BailMobilite::factory()->create(['status' => 'assigned']);
        $inProgressBM = BailMobilite::factory()->create(['status' => 'in_progress']);

        $inProgress = BailMobilite::inProgress()->get();

        $this->assertTrue($inProgress->contains($inProgressBM));
        $this->assertFalse($inProgress->contains($assignedBM));
    }

    /** @test */
    public function it_has_completed_scope()
    {
        $completedBM = BailMobilite::factory()->create(['status' => 'completed']);
        $assignedBM = BailMobilite::factory()->create(['status' => 'assigned']);

        $completed = BailMobilite::completed()->get();

        $this->assertTrue($completed->contains($completedBM));
        $this->assertFalse($completed->contains($assignedBM));
    }

    /** @test */
    public function it_has_incident_scope()
    {
        $incidentBM = BailMobilite::factory()->create(['status' => 'incident']);
        $assignedBM = BailMobilite::factory()->create(['status' => 'assigned']);

        $incident = BailMobilite::incident()->get();

        $this->assertTrue($incident->contains($incidentBM));
        $this->assertFalse($incident->contains($assignedBM));
    }

    /** @test */
    public function it_validates_status_enum()
    {
        $validStatuses = ['assigned', 'in_progress', 'completed', 'incident'];

        foreach ($validStatuses as $status) {
            $bailMobilite = BailMobilite::factory()->create(['status' => $status]);
            $this->assertEquals($status, $bailMobilite->status);
        }
    }

    /** @test */
    public function it_has_default_status_assigned()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $this->assertEquals('assigned', $bailMobilite->status);
    }
}