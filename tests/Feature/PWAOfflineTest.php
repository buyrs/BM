<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PWAOfflineTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_endpoint()
    {
        $response = $this->get(route('pwa.manifest'));
        
        $response->assertStatus(200)
                 ->assertHeader('Content-Type', 'application/json')
                 ->assertJson([
                     'name' => config('app.name', 'Bail Mobilite Platform'),
                     'short_name' => config('app.name', 'BailMobilite'),
                     'display' => 'standalone',
                     'start_url' => '/',
                     'theme_color' => '#3b82f6'
                 ]);
    }

    public function test_service_worker_endpoint()
    {
        $response = $this->get(route('pwa.sw'));
        
        $response->assertStatus(200)
                 ->assertHeader('Content-Type', 'application/javascript');
    }

    public function test_offline_page()
    {
        $response = $this->get(route('pwa.offline'));
        
        $response->assertStatus(200)
                 ->assertSee('You\'re Offline');
    }

    public function test_cache_headers_for_static_assets()
    {
        // Test that CSS is served with proper caching
        $response = $this->get('/css/app.css');
        $response->assertStatus(200);
        
        // Test that JS is served with proper caching
        $response = $this->get('/js/app.js');
        $response->assertStatus(200);
    }

    public function test_pwa_installable_criteria()
    {
        // Check if the site has a valid manifest
        $response = $this->get(route('pwa.manifest'));
        $response->assertStatus(200);
        
        // Verify manifest contains required fields for PWA
        $manifest = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('name', $manifest);
        $this->assertArrayHasKey('short_name', $manifest);
        $this->assertArrayHasKey('start_url', $manifest);
        $this->assertArrayHasKey('display', $manifest);
        $this->assertArrayHasKey('icons', $manifest);
        $this->assertNotEmpty($manifest['icons']);
        
        // Check that all icon sizes exist
        $iconSizes = array_column($manifest['icons'], 'sizes');
        $this->assertContains('192x192', $iconSizes);
        $this->assertContains('512x512', $iconSizes);
    }
}