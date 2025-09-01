<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\ContractTemplate;
use App\Models\User;
use App\Models\BailMobiliteSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ContractTemplateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $fillable = [
            'name', 'type', 'content', 'admin_signature',
            'admin_signed_at', 'is_active', 'created_by'
        ];

        $template = new ContractTemplate();
        $this->assertEquals($fillable, $template->getFillable());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $template = ContractTemplate::factory()->create([
            'admin_signed_at' => '2025-01-15 10:30:00',
            'is_active' => true
        ]);

        $this->assertInstanceOf(Carbon::class, $template->admin_signed_at);
        $this->assertIsBool($template->is_active);
    }

    /** @test */
    public function it_belongs_to_creator()
    {
        $user = User::factory()->create();
        $template = ContractTemplate::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $template->creator);
        $this->assertEquals($user->id, $template->creator->id);
    }

    /** @test */
    public function it_has_many_signatures()
    {
        $template = ContractTemplate::factory()->create();
        $signature = BailMobiliteSignature::factory()->create(['contract_template_id' => $template->id]);

        $this->assertTrue($template->signatures->contains($signature));
    }

    /** @test */
    public function it_has_active_scope()
    {
        $activeTemplate = ContractTemplate::factory()->create(['is_active' => true]);
        $inactiveTemplate = ContractTemplate::factory()->create(['is_active' => false]);

        $active = ContractTemplate::active()->get();

        $this->assertTrue($active->contains($activeTemplate));
        $this->assertFalse($active->contains($inactiveTemplate));
    }

    /** @test */
    public function it_has_entry_scope()
    {
        $entryTemplate = ContractTemplate::factory()->create(['type' => 'entry']);
        $exitTemplate = ContractTemplate::factory()->create(['type' => 'exit']);

        $entry = ContractTemplate::entry()->get();

        $this->assertTrue($entry->contains($entryTemplate));
        $this->assertFalse($entry->contains($exitTemplate));
    }

    /** @test */
    public function it_has_exit_scope()
    {
        $entryTemplate = ContractTemplate::factory()->create(['type' => 'entry']);
        $exitTemplate = ContractTemplate::factory()->create(['type' => 'exit']);

        $exit = ContractTemplate::exit()->get();

        $this->assertTrue($exit->contains($exitTemplate));
        $this->assertFalse($exit->contains($entryTemplate));
    }

    /** @test */
    public function it_validates_type_enum()
    {
        $validTypes = ['entry', 'exit'];

        foreach ($validTypes as $type) {
            $template = ContractTemplate::factory()->create(['type' => $type]);
            $this->assertEquals($type, $template->type);
        }
    }

    /** @test */
    public function it_has_default_active_status()
    {
        $template = ContractTemplate::factory()->create(['is_active' => null]);
        $this->assertTrue($template->fresh()->is_active);
    }

    /** @test */
    public function it_can_be_signed_by_admin()
    {
        $template = ContractTemplate::factory()->create([
            'admin_signature' => null,
            'admin_signed_at' => null
        ]);

        $signature = 'admin_signature_data';
        $signedAt = now();

        $template->update([
            'admin_signature' => $signature,
            'admin_signed_at' => $signedAt
        ]);

        $this->assertEquals($signature, $template->admin_signature);
        $this->assertEquals($signedAt->format('Y-m-d H:i:s'), $template->admin_signed_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_be_activated_and_deactivated()
    {
        $template = ContractTemplate::factory()->create(['is_active' => false]);

        $template->update(['is_active' => true]);
        $this->assertTrue($template->is_active);

        $template->update(['is_active' => false]);
        $this->assertFalse($template->is_active);
    }
}