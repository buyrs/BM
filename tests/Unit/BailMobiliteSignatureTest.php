<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\BailMobiliteSignature;
use App\Models\BailMobilite;
use App\Models\ContractTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class BailMobiliteSignatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $fillable = [
            'bail_mobilite_id', 'signature_type', 'contract_template_id',
            'tenant_signature', 'tenant_signed_at', 'contract_pdf_path',
            'signature_metadata'
        ];

        $signature = new BailMobiliteSignature();
        $this->assertEquals($fillable, $signature->getFillable());
    }

    /** @test */
    public function it_casts_dates_correctly()
    {
        $signature = BailMobiliteSignature::factory()->create([
            'tenant_signed_at' => '2025-01-15 10:30:00'
        ]);

        $this->assertInstanceOf(Carbon::class, $signature->tenant_signed_at);
    }

    /** @test */
    public function it_belongs_to_bail_mobilite()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $signature = BailMobiliteSignature::factory()->create(['bail_mobilite_id' => $bailMobilite->id]);

        $this->assertInstanceOf(BailMobilite::class, $signature->bailMobilite);
        $this->assertEquals($bailMobilite->id, $signature->bailMobilite->id);
    }

    /** @test */
    public function it_belongs_to_contract_template()
    {
        $template = ContractTemplate::factory()->create();
        $signature = BailMobiliteSignature::factory()->create(['contract_template_id' => $template->id]);

        $this->assertInstanceOf(ContractTemplate::class, $signature->contractTemplate);
        $this->assertEquals($template->id, $signature->contractTemplate->id);
    }

    /** @test */
    public function it_validates_signature_type_enum()
    {
        $validTypes = ['entry', 'exit'];

        foreach ($validTypes as $type) {
            $signature = BailMobiliteSignature::factory()->create(['signature_type' => $type]);
            $this->assertEquals($type, $signature->signature_type);
        }
    }

    /** @test */
    public function it_can_check_if_complete_with_both_signatures()
    {
        $template = ContractTemplate::factory()->create([
            'admin_signature' => 'admin_signature_data'
        ]);
        
        $signature = BailMobiliteSignature::factory()->create([
            'contract_template_id' => $template->id,
            'tenant_signature' => 'tenant_signature_data'
        ]);

        $this->assertTrue($signature->isComplete());
    }

    /** @test */
    public function it_is_not_complete_without_tenant_signature()
    {
        $template = ContractTemplate::factory()->create([
            'admin_signature' => 'admin_signature_data'
        ]);
        
        $signature = BailMobiliteSignature::factory()->create([
            'contract_template_id' => $template->id,
            'tenant_signature' => null
        ]);

        $this->assertFalse($signature->isComplete());
    }

    /** @test */
    public function it_is_not_complete_without_admin_signature()
    {
        $template = ContractTemplate::factory()->create([
            'admin_signature' => null
        ]);
        
        $signature = BailMobiliteSignature::factory()->create([
            'contract_template_id' => $template->id,
            'tenant_signature' => 'tenant_signature_data'
        ]);

        $this->assertFalse($signature->isComplete());
    }

    /** @test */
    public function it_is_not_complete_without_any_signatures()
    {
        $template = ContractTemplate::factory()->create([
            'admin_signature' => null
        ]);
        
        $signature = BailMobiliteSignature::factory()->create([
            'contract_template_id' => $template->id,
            'tenant_signature' => null
        ]);

        $this->assertFalse($signature->isComplete());
    }

    /** @test */
    public function it_can_store_pdf_path()
    {
        $pdfPath = 'contracts/bail_mobilite_123_entry.pdf';
        $signature = BailMobiliteSignature::factory()->create([
            'contract_pdf_path' => $pdfPath
        ]);

        $this->assertEquals($pdfPath, $signature->contract_pdf_path);
    }

    /** @test */
    public function it_can_be_signed_by_tenant()
    {
        $signature = BailMobiliteSignature::factory()->create([
            'tenant_signature' => null,
            'tenant_signed_at' => null
        ]);

        $tenantSignature = 'tenant_signature_data';
        $signedAt = now();

        $signature->update([
            'tenant_signature' => $tenantSignature,
            'tenant_signed_at' => $signedAt
        ]);

        $this->assertEquals($tenantSignature, $signature->tenant_signature);
        $this->assertEquals($signedAt->format('Y-m-d H:i:s'), $signature->tenant_signed_at->format('Y-m-d H:i:s'));
    }
}