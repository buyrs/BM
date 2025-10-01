<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ApiTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'api:test {--endpoint=all : Specific endpoint to test} {--user-id=1 : User ID for authentication}';

    /**
     * The console command description.
     */
    protected $description = 'Test API endpoints for functionality and response format';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting API endpoint tests...');

        $userId = $this->option('user-id');
        $endpoint = $this->option('endpoint');

        // Get or create test user
        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return 1;
        }

        // Create API token for testing
        $token = $user->createToken('test-token')->plainTextToken;
        $baseUrl = config('app.url');

        $this->info("Testing with user: {$user->name} ({$user->email})");
        $this->info("Base URL: {$baseUrl}");

        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $tests = [
            'health' => [
                'method' => 'GET',
                'url' => '/api/health',
                'description' => 'API Health Check',
            ],
            'info' => [
                'method' => 'GET',
                'url' => '/api/info',
                'description' => 'API Information',
            ],
            'auth_profile' => [
                'method' => 'GET',
                'url' => '/api/v1/auth/profile',
                'description' => 'Get User Profile',
                'auth' => true,
            ],
            'properties_list' => [
                'method' => 'GET',
                'url' => '/api/v1/properties',
                'description' => 'List Properties',
                'auth' => true,
            ],
            'properties_stats' => [
                'method' => 'GET',
                'url' => '/api/v1/properties/statistics',
                'description' => 'Property Statistics',
                'auth' => true,
            ],
            'missions_list' => [
                'method' => 'GET',
                'url' => '/api/v1/missions',
                'description' => 'List Missions',
                'auth' => true,
            ],
            'notifications_list' => [
                'method' => 'GET',
                'url' => '/api/v1/notifications',
                'description' => 'List Notifications',
                'auth' => true,
            ],
            'notifications_unread' => [
                'method' => 'GET',
                'url' => '/api/v1/notifications/unread-count',
                'description' => 'Unread Notification Count',
                'auth' => true,
            ],
        ];

        // Filter tests if specific endpoint requested
        if ($endpoint !== 'all' && isset($tests[$endpoint])) {
            $tests = [$endpoint => $tests[$endpoint]];
        } elseif ($endpoint !== 'all') {
            $this->error("Unknown endpoint: {$endpoint}");
            return 1;
        }

        $passed = 0;
        $failed = 0;

        foreach ($tests as $testName => $test) {
            $this->info("\n" . str_repeat('-', 50));
            $this->info("Testing: {$test['description']}");
            $this->info("Endpoint: {$test['method']} {$test['url']}");

            try {
                $requestHeaders = $test['auth'] ?? false ? $headers : ['Accept' => 'application/json'];
                
                $response = Http::withHeaders($requestHeaders)
                    ->timeout(10)
                    ->{strtolower($test['method'])}($baseUrl . $test['url']);

                $statusCode = $response->status();
                $responseData = $response->json();

                $this->info("Status Code: {$statusCode}");

                if ($statusCode >= 200 && $statusCode < 300) {
                    $this->info("✅ PASSED");
                    
                    // Validate response structure
                    if (isset($responseData['success'])) {
                        $this->info("Response format: Valid");
                        if ($responseData['success']) {
                            $this->info("Success: true");
                        } else {
                            $message = $responseData['message'] ?? 'No message';
                            $this->warn("Success: false - {$message}");
                        }
                    } else {
                        $this->warn("Response format: Missing 'success' field");
                    }

                    $passed++;
                } else {
                    $this->error("❌ FAILED");
                    $this->error("Response: " . json_encode($responseData, JSON_PRETTY_PRINT));
                    $failed++;
                }

            } catch (\Exception $e) {
                $this->error("❌ FAILED - Exception: " . $e->getMessage());
                $failed++;
            }
        }

        // Clean up test token
        $user->tokens()->where('name', 'test-token')->delete();

        $this->info("\n" . str_repeat('=', 50));
        $this->info("Test Results:");
        $this->info("✅ Passed: {$passed}");
        $this->info("❌ Failed: {$failed}");
        $this->info("Total: " . ($passed + $failed));

        if ($failed > 0) {
            $this->error("\nSome tests failed. Please check the API implementation.");
            return 1;
        }

        $this->info("\n🎉 All tests passed!");
        return 0;
    }
}