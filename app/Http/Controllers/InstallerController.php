<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use PDO;
use PDOException;

class InstallerController extends Controller
{
    public function index()
    {
        // Check if already installed
        if (file_exists(storage_path('installed'))) {
            return redirect()->route('welcome');
        }

        $requirements = $this->checkRequirements();
        $permissions = $this->checkPermissions();
        
        return view('installer.index', compact('requirements', 'permissions'));
    }

    public function database(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'db_host' => 'required',
            'db_port' => 'required',
            'db_database' => 'required',
            'db_username' => 'required',
            'db_password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $connection = new PDO(
                "mysql:host={$request->db_host};port={$request->db_port}",
                $request->db_username,
                $request->db_password
            );
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Test database creation
            $connection->exec("CREATE DATABASE IF NOT EXISTS `{$request->db_database}`");
            
            // Update .env file
            $this->updateEnvironmentFile([
                'DB_HOST' => $request->db_host,
                'DB_PORT' => $request->db_port,
                'DB_DATABASE' => $request->db_database,
                'DB_USERNAME' => $request->db_username,
                'DB_PASSWORD' => $request->db_password,
            ]);

            return response()->json(['message' => 'Database connection successful']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Database connection failed: ' . $e->getMessage()], 422);
        }
    }

    public function install(Request $request)
    {
        try {
            // Run migrations
            Artisan::call('migrate:fresh', ['--force' => true]);

            // Run seeders
            Artisan::call('db:seed', ['--force' => true]);

            // Generate application key
            Artisan::call('key:generate', ['--force' => true]);

            // Optimize Laravel
            Artisan::call('optimize');
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            // Create installed file
            File::put(storage_path('installed'), 'Installation completed on ' . date('Y-m-d H:i:s'));

            return response()->json(['message' => 'Installation completed successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Installation failed: ' . $e->getMessage()], 500);
        }
    }

    private function checkRequirements()
    {
        $requirements = [
            'php' => [
                'version' => '8.1.0',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.1.0', '>=')
            ],
            'extensions' => [
                'pdo' => extension_loaded('pdo'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'mbstring' => extension_loaded('mbstring'),
                'xml' => extension_loaded('xml'),
                'curl' => extension_loaded('curl'),
                'gd' => extension_loaded('gd'),
                'zip' => extension_loaded('zip'),
            ],
            'functions' => [
                'proc_open' => function_exists('proc_open'),
                'proc_get_status' => function_exists('proc_get_status'),
            ]
        ];

        return $requirements;
    }

    private function checkPermissions()
    {
        $paths = [
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
            '.env' => base_path('.env'),
        ];

        $permissions = [];
        foreach ($paths as $path => $fullPath) {
            $permissions[$path] = [
                'path' => $path,
                'writable' => is_writable($fullPath),
            ];
        }

        return $permissions;
    }

    private function updateEnvironmentFile($data)
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";
            
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        file_put_contents($envFile, $envContent);
    }
} 