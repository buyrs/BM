<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\ContractTemplateController;
use App\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class ContractTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    protected ContractTemplateController $controller;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ContractTemplateController();
        
        // Create admin role and user
        Role::create(['name' => 'super-admin']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super-admin');
        
        $this->actingAs($this->adminUser);
    }

    /** @test */
    public function it_can_create_contract_template()
    {
        $data = [
            'name' => 'Standard Entry Contract',
            'type' => 'entry',
            'content' => 'This is the contract content with legal terms...',
            'is_active' => true
        ];

        $request = new Request($data);
        $response = $this->controller->store($request);

        $this->assertDatabaseHas('contract_templates', [
            'name' => 'Standard Entry Contract',
            'type' => 'entry',
            'content' => 'This is the contract content with legal terms...',
            'is_active' => true,
            'created_by' => $this->adminUser->id
        ]);
    }

    /** @test */
    public function it_can_update_contract_template()
    {
        $template = ContractTemplate::factory()->create([
            'name' => 'Old Name',
            'content' => 'Old content',
            'created_by' => $this->adminUser->id
        ]);

        $data = [
            'name' => 'Updated Name',
            'type' => $template->type,
            'content' => 'Updated content with new terms',
            'is_active' => $template->is_active
        ];

        $request = new Request($data);
        $response = $this->controller->update($template, $request);

        $template->refresh();
        $this->assertEquals('Updated Name', $template->name);
        $this->assertEquals('Updated content with new terms', $template->content);
    }

    /** @test */
    public function it_can_sign_template_as_admin()
    {
        $template = ContractTemplate::factory()->create([
            'admin_signature' => null,
            'admin_signed_at' => null,
            'created_by' => $this->adminUser->id
        ]);

        $request = new Request([
            'signature' => 'admin_signature_data_12345'
        ]);

        $response = $this->controller->signTemplate($template, $request);

        $template->refresh();
        $this->assertEquals('admin_signature_data_12345', $template->admin_signature);
        $this->assertNotNull($template->admin_signed_at);
    }

    /** @test */
    public function it_can_activate_template()
    {
        $template = ContractTemplate::factory()->create([
            'is_active' => false,
            'created_by' => $this->adminUser->id
        ]);

        $request = new Request(['is_active' => true]);
        $response = $this->controller->activate($template, $request);

        $this->assertTrue($template->fresh()->is_active);
    }

    /** @test */
    public function it_can_deactivate_template()
    {
        $template = ContractTemplate::factory()->create([
            'is_active' => true,
            'created_by' => $this->adminUser->id
        ]);

        $request = new Request(['is_active' => false]);
        $response = $this->controller->activate($template, $request);

        $this->assertFalse($template->fresh()->is_active);
    }

    /** @test */
    public function it_can_preview_contract()
    {
        $template = ContractTemplate::factory()->create([
            'content' => 'Contract for {{tenant_name}} at {{address}}',
            'admin_signature' => 'admin_signature_data',
            'created_by' => $this->adminUser->id
        ]);

        $request = new Request([
            'tenant_name' => 'John Doe',
            'address' => '123 Test Street',
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28'
        ]);

        $response = $this->controller->preview($template, $request);
        $data = $response->getData();

        $this->assertStringContains('John Doe', $data['preview_content']);
        $this->assertStringContains('123 Test Street', $data['preview_content']);
        $this->assertEquals('admin_signature_data', $data['admin_signature']);
    }

    /** @test */
    public function it_filters_templates_by_type()
    {
        $entryTemplate = ContractTemplate::factory()->create(['type' => 'entry']);
        $exitTemplate = ContractTemplate::factory()->create(['type' => 'exit']);

        $request = new Request(['type' => 'entry']);
        $response = $this->controller->index($request);
        $data = $response->getData();

        $templates = collect($data['templates']);
        $this->assertTrue($templates->contains('id', $entryTemplate->id));
        $this->assertFalse($templates->contains('id', $exitTemplate->id));
    }

    /** @test */
    public function it_filters_templates_by_active_status()
    {
        $activeTemplate = ContractTemplate::factory()->create(['is_active' => true]);
        $inactiveTemplate = ContractTemplate::factory()->create(['is_active' => false]);

        $request = new Request(['active' => true]);
        $response = $this->controller->index($request);
        $data = $response->getData();

        $templates = collect($data['templates']);
        $this->assertTrue($templates->contains('id', $activeTemplate->id));
        $this->assertFalse($templates->contains('id', $inactiveTemplate->id));
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $request = new Request([
            'name' => '', // Missing required field
            'type' => 'entry',
            'content' => 'Some content'
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->controller->store($request);
    }

    /** @test */
    public function it_validates_type_enum_on_create()
    {
        $request = new Request([
            'name' => 'Test Template',
            'type' => 'invalid_type', // Invalid enum value
            'content' => 'Some content'
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->controller->store($request);
    }

    /** @test */
    public function it_requires_admin_permission_for_actions()
    {
        // Create a user without admin role
        $regularUser = User::factory()->create();
        $this->actingAs($regularUser);

        $request = new Request([
            'name' => 'Test Template',
            'type' => 'entry',
            'content' => 'Some content'
        ]);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->controller->store($request);
    }

    /** @test */
    public function it_can_delete_template()
    {
        $template = ContractTemplate::factory()->create([
            'created_by' => $this->adminUser->id
        ]);

        $response = $this->controller->destroy($template);

        $this->assertDatabaseMissing('contract_templates', [
            'id' => $template->id
        ]);
    }

    /** @test */
    public function it_cannot_delete_template_with_signatures()
    {
        $template = ContractTemplate::factory()->create([
            'created_by' => $this->adminUser->id
        ]);
        
        // Create a signature that references this template
        \App\Models\BailMobiliteSignature::factory()->create([
            'contract_template_id' => $template->id
        ]);

        $this->expectException(\Exception::class);
        $this->controller->destroy($template);
    }
}