<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ScalabilityTestingService
{
    /**
     * Perform load testing against the application
     *
     * @param string $baseUrl The base URL to test
     * @param array $testConfig Configuration for the load test
     * @return array Results of the load test
     */
    public function performLoadTest(string $baseUrl, array $testConfig): array
    {
        $results = [
            'start_time' => now(),
            'requests_sent' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'total_time' => 0,
            'average_response_time' => 0,
            'requests_per_second' => 0,
            'errors' => [],
            'response_times' => [],
            'status_codes' => []
        ];

        $concurrentUsers = $testConfig['concurrent_users'] ?? 10;
        $duration = $testConfig['duration'] ?? 60; // in seconds
        $requestsPerUser = $testConfig['requests_per_user'] ?? 10;
        $endpoints = $testConfig['endpoints'] ?? ['/'];

        $startTime = microtime(true);
        
        try {
            // For this implementation, we'll simulate the load test
            // In a real scenario, you'd use a tool like Artillery, k6, or JMeter
            
            $totalRequests = $concurrentUsers * $requestsPerUser;
            
            for ($i = 0; $i < $totalRequests; $i++) {
                $endpoint = $endpoints[array_rand($endpoints)];
                $url = rtrim($baseUrl, '/') . $endpoint;
                
                $requestStartTime = microtime(true);
                
                try {
                    $response = Http::timeout(30)->get($url);
                    $responseTime = microtime(true) - $requestStartTime;
                    
                    $results['requests_sent']++;
                    $results['response_times'][] = $responseTime;
                    $results['status_codes'][] = $response->status();
                    
                    if ($response->successful()) {
                        $results['successful_requests']++;
                    } else {
                        $results['failed_requests']++;
                        $results['errors'][] = [
                            'url' => $url,
                            'status' => $response->status(),
                            'response_time' => $responseTime
                        ];
                    }
                } catch (\Exception $e) {
                    $responseTime = microtime(true) - $requestStartTime;
                    $results['requests_sent']++;
                    $results['failed_requests']++;
                    $results['response_times'][] = $responseTime;
                    
                    $results['errors'][] = [
                        'url' => $url,
                        'error' => $e->getMessage(),
                        'response_time' => $responseTime
                    ];
                }
                
                // Small delay to simulate realistic user behavior
                usleep(rand(100000, 500000)); // 0.1 to 0.5 seconds
            }
            
            $results['total_time'] = microtime(true) - $startTime;
            
            if (!empty($results['response_times'])) {
                $results['average_response_time'] = array_sum($results['response_times']) / count($results['response_times']);
            }
            
            if ($results['total_time'] > 0) {
                $results['requests_per_second'] = $results['requests_sent'] / $results['total_time'];
            }
            
        } catch (\Exception $e) {
            $results['errors'][] = [
                'type' => 'test_error',
                'message' => $e->getMessage()
            ];
        }
        
        $results['end_time'] = now();
        $results['test_duration'] = $results['end_time']->diffInSeconds($results['start_time']);
        
        return $results;
    }

    /**
     * Perform stress testing to find breaking point
     *
     * @param string $baseUrl
     * @param array $config
     * @return array
     */
    public function performStressTest(string $baseUrl, array $config): array
    {
        $results = [
            'start_time' => now(),
            'peak_concurrent_users' => 0,
            'max_requests_per_second' => 0,
            'breakpoint_found' => false,
            'breakpoint_details' => null,
            'test_phases' => []
        ];

        $initialUsers = $config['initial_users'] ?? 5;
        $increment = $config['increment'] ?? 5;
        $maxUsers = $config['max_users'] ?? 100;
        $rampUpTime = $config['ramp_up_time'] ?? 30; // seconds to ramp up each phase
        $testDuration = $config['test_duration'] ?? 60; // seconds to hold each phase
        $errorThreshold = $config['error_threshold'] ?? 0.1; // 10% error rate
        $maxAvgResponseTime = $config['max_avg_response_time'] ?? 5000; // 5 seconds in ms

        $currentUsers = $initialUsers;

        while ($currentUsers <= $maxUsers) {
            Log::info("Starting stress test phase", ['users' => $currentUsers]);

            // Perform a load test for this user count
            $phaseConfig = [
                'concurrent_users' => $currentUsers,
                'duration' => $testDuration,
                'requests_per_user' => 5,
                'endpoints' => $config['endpoints'] ?? ['/']
            ];

            $phaseResults = $this->performLoadTest($baseUrl, $phaseConfig);

            $results['test_phases'][] = [
                'users' => $currentUsers,
                'results' => $phaseResults
            ];

            // Check if we've hit the breaking point
            $errorRate = $phaseResults['requests_sent'] > 0 
                ? $phaseResults['failed_requests'] / $phaseResults['requests_sent'] 
                : 0;

            $avgResponseTimeMs = $phaseResults['average_response_time'] * 1000;

            if ($errorRate >= $errorThreshold || $avgResponseTimeMs >= $maxAvgResponseTime) {
                $results['breakpoint_found'] = true;
                $results['breakpoint_details'] = [
                    'users_at_breakpoint' => $currentUsers,
                    'error_rate' => $errorRate,
                    'avg_response_time_ms' => $avgResponseTimeMs,
                    'requests_per_second' => $phaseResults['requests_per_second']
                ];
                break;
            }

            // Update peak metrics
            $results['peak_concurrent_users'] = max($results['peak_concurrent_users'], $currentUsers);
            $results['max_requests_per_second'] = max($results['max_requests_per_second'], $phaseResults['requests_per_second']);

            $currentUsers += $increment;
        }

        $results['end_time'] = now();

        return $results;
    }

    /**
     * Perform soak testing (sustained load)
     *
     * @param string $baseUrl
     * @param array $config
     * @return array
     */
    public function performSoakTest(string $baseUrl, array $config): array
    {
        $results = [
            'start_time' => now(),
            'duration_hours' => $config['duration_hours'] ?? 1,
            'concurrent_users' => $config['concurrent_users'] ?? 10,
            'requests_per_minute' => $config['requests_per_minute'] ?? 10,
            'hourly_metrics' => [],
            'memory_usage_trends' => [],
            'degradation_detected' => false,
            'degradation_details' => null
        ];

        $endTime = now()->addHours($results['duration_hours']);
        $hourMetrics = [];
        $previousAvgTime = 0;

        while (now()->lessThan($endTime)) {
            $hourStart = now();
            $hourEnd = $hourStart->copy()->addHour();
            
            $hourlyResults = [
                'start_time' => $hourStart,
                'requests_sent' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'average_response_time' => 0,
                'errors' => []
            ];
            
            // Send requests for one hour (or until the test period ends)
            $requestsThisHour = 0;
            $maxRequestsThisHour = $results['requests_per_minute'] * 60;
            
            while (now()->lessThan($hourEnd) && $requestsThisHour < $maxRequestsThisHour) {
                $endpoint = $config['endpoints'][array_rand($config['endpoints'] ?? ['/'])] ?? '/';
                $url = rtrim($baseUrl, '/') . $endpoint;
                
                try {
                    $startTime = microtime(true);
                    $response = Http::timeout(30)->get($url);
                    $responseTime = microtime(true) - $startTime;
                    
                    $hourlyResults['requests_sent']++;
                    $hourlyResults['response_times'][] = $responseTime;
                    
                    if ($response->successful()) {
                        $hourlyResults['successful_requests']++;
                    } else {
                        $hourlyResults['failed_requests']++;
                    }
                } catch (\Exception $e) {
                    $hourlyResults['requests_sent']++;
                    $hourlyResults['failed_requests']++;
                    $hourlyResults['errors'][] = $e->getMessage();
                }
                
                $requestsThisHour++;
                sleep(60 / $results['requests_per_minute']); // Space out requests
            }
            
            if (!empty($hourlyResults['response_times'])) {
                $hourlyResults['average_response_time'] = 
                    array_sum($hourlyResults['response_times']) / count($hourlyResults['response_times']) * 1000; // Convert to ms
            }
            
            $results['hourly_metrics'][] = $hourlyResults;
            
            // Check for degradation over time
            if ($previousAvgTime > 0 && $hourlyResults['average_response_time'] > 0) {
                $degradationPct = ($hourlyResults['average_response_time'] - $previousAvgTime) / $previousAvgTime * 100;
                if ($degradationPct > 50) { // More than 50% degradation
                    $results['degradation_detected'] = true;
                    $results['degradation_details'] = [
                        'hour' => count($results['hourly_metrics']),
                        'degradation_percent' => $degradationPct,
                        'previous_avg_response_ms' => $previousAvgTime,
                        'current_avg_response_ms' => $hourlyResults['average_response_time']
                    ];
                }
            }
            
            $previousAvgTime = $hourlyResults['average_response_time'];
            
            Log::info("Soak test hour completed", ['hour' => count($results['hourly_metrics'])]);
        }
        
        $results['end_time'] = now();
        $results['actual_duration_hours'] = $results['end_time']->diffInHours($results['start_time']);

        return $results;
    }

    /**
     * Generate scalability test report
     *
     * @param array $testResults
     * @return array
     */
    public function generateTestReport(array $testResults): array
    {
        $report = [
            'generated_at' => now(),
            'test_type' => $testResults['test_type'] ?? 'load_test',
            'summary' => [
                'total_requests' => $testResults['requests_sent'] ?? 0,
                'successful_requests' => $testResults['successful_requests'] ?? 0,
                'failed_requests' => $testResults['failed_requests'] ?? 0,
                'success_rate' => 0,
                'avg_response_time_ms' => ($testResults['average_response_time'] ?? 0) * 1000,
                'requests_per_second' => $testResults['requests_per_second'] ?? 0,
            ],
            'recommendations' => []
        ];

        if ($testResults['requests_sent'] > 0) {
            $report['summary']['success_rate'] = 
                ($testResults['successful_requests'] / $testResults['requests_sent']) * 100;
        }

        // Generate recommendations based on results
        if ($report['summary']['success_rate'] < 95) {
            $report['recommendations'][] = 'Success rate is below 95%. Consider infrastructure improvements.';
        }

        if ($report['summary']['avg_response_time_ms'] > 2000) {
            $report['recommendations'][] = 'Average response time is above 2 seconds. Optimize database queries and implement caching.';
        }

        if ($testResults['breakpoint_found'] ?? false) {
            $report['recommendations'][] = 'System reached breaking point at ' . 
                ($testResults['breakpoint_details']['users_at_breakpoint'] ?? 0) . 
                ' concurrent users. Consider horizontal scaling.';
        }

        if ($testResults['degradation_detected'] ?? false) {
            $report['recommendations'][] = 'Performance degradation detected during soak test. ' .
                'Check for memory leaks or resource exhaustion.';
        }

        // Add capacity planning recommendations
        if ($report['summary']['requests_per_second'] > 10) {
            $report['recommendations'][] = 'High RPS detected. Consider implementing CDN and reverse proxy caching.';
        }

        // Calculate percentiles for response time analysis
        if (isset($testResults['response_times']) && !empty($testResults['response_times'])) {
            $responseTimes = $testResults['response_times'];
            sort($responseTimes);
            
            $report['percentiles'] = [
                'p50' => $this->getPercentile($responseTimes, 50) * 1000, // Convert to ms
                'p90' => $this->getPercentile($responseTimes, 90) * 1000,
                'p95' => $this->getPercentile($responseTimes, 95) * 1000,
                'p99' => $this->getPercentile($responseTimes, 99) * 1000,
            ];
        }

        return $report;
    }

    /**
     * Get percentile value from sorted array
     *
     * @param array $data
     * @param int $percentile
     * @return float
     */
    private function getPercentile(array $data, int $percentile): float
    {
        if (empty($data)) {
            return 0;
        }

        $index = ($percentile / 100) * (count($data) - 1);
        $lower = floor($index);
        $upper = ceil($index);
        $weight = $index - $lower;

        if ($lower == $upper) {
            return $data[$lower];
        }

        return $data[$lower] * (1 - $weight) + $data[$upper] * $weight;
    }

    /**
     * Schedule and run scalability tests
     *
     * @param string $testType
     * @param array $config
     * @param string|null $webhookUrl
     * @return array
     */
    public function scheduleTest(string $testType, array $config, ?string $webhookUrl = null): array
    {
        $results = match($testType) {
            'load' => $this->performLoadTest($config['base_url'] ?? config('app.url'), $config),
            'stress' => $this->performStressTest($config['base_url'] ?? config('app.url'), $config),
            'soak' => $this->performSoakTest($config['base_url'] ?? config('app.url'), $config),
            default => $this->performLoadTest($config['base_url'] ?? config('app.url'), $config)
        };

        $results['test_type'] = $testType;

        // Generate report
        $report = $this->generateTestReport($results);

        // Send webhook notification if provided
        if ($webhookUrl) {
            try {
                Http::post($webhookUrl, [
                    'test_type' => $testType,
                    'results' => $results,
                    'report' => $report
                ]);
            } catch (\Exception $e) {
                Log::error('Webhook notification failed: ' . $e->getMessage());
            }
        }

        return [
            'results' => $results,
            'report' => $report
        ];
    }
}