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
            'db_connection' => 'required|in:mysql,sqlite',
            'db_host' => 'required_if:db_connection,mysql',
            'db_port' => 'required_if:db_connection,mysql',
            'db_database' => 'required',
            'db_username' => 'required_if:db_connection,mysql',
            'db_password' => 'required_if:db_connection,mysql',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'request_data' => $request->all()], 422);
        }

        try {
            // Simply update .env file without testing connection
            $envData = [
                'DB_CONNECTION' => $request->db_connection,
            ];
            
            if ($request->db_connection === 'mysql') {
                $envData['DB_HOST'] = $request->db_host;
                $envData['DB_PORT'] = $request->db_port;
                $envData['DB_DATABASE'] = $request->db_database;
                $envData['DB_USERNAME'] = $request->db_username;
                $envData['DB_PASSWORD'] = $request->db_password;
            } else {
                // For SQLite, we just need the database name (path will be relative to database_path())
                $envData['DB_DATABASE'] = $request->db_database;
                // Remove MySQL specific entries if they exist
                $this->removeEnvironmentVariables(['DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD']);
            }

            \Log::info("Attempting to update .env file with data", ['env_data' => $envData]);
            
            $result = $this->updateEnvironmentFile($envData);
            
            \Log::info("Environment file update result", ['result' => $result]);
            
            return response()->json(['message' => 'Database configuration saved successfully']);
        } catch (\Exception $e) {
            \Log::error("Database configuration failed", [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Database configuration failed: ' . $e->getMessage(), 
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ], 500);
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
        \Log::info("Updating environment file", ['file_path' => $envFile, 'data_keys' => array_keys($data)]);
        
        if (!file_exists($envFile)) {
            throw new \Exception("Environment file does not exist: " . $envFile);
        }
        
        if (!is_writable($envFile)) {
            throw new \Exception("Environment file is not writable: " . $envFile);
        }
        
        $envContent = file_get_contents($envFile);
        \Log::info("Current .env content length", ['length' => strlen($envContent)]);
        
        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";
            
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
                \Log::info("Updated existing key", ['key' => $key]);
            } else {
                $envContent .= "\n{$replacement}";
                \Log::info("Added new key", ['key' => $key]);
            }
        }

        $result = file_put_contents($envFile, $envContent);
        \Log::info("File write result", ['bytes_written' => $result]);
        
        if ($result === false) {
            throw new \Exception("Failed to write to environment file: " . $envFile);
        }
        
        return $result;
    }

    private function removeEnvironmentVariables($keys)
    {
        $envFile = base_path('.env');
        \Log::info("Removing environment variables", ['file_path' => $envFile, 'keys' => $keys]);
        
        if (!file_exists($envFile)) {
            \Log::warning("Environment file does not exist when trying to remove variables", ['file_path' => $envFile]);
            return;
        }
        
        $envContent = file_get_contents($envFile);
        
        foreach ($keys as $key) {
            $pattern = "/^{$key}=.*\n/m";
            $count = 0;
            $envContent = preg_replace($pattern, '', $envContent, -1, $count);
            \Log::info("Removed environment variable", ['key' => $key, 'count' => $count]);
        }

        file_put_contents($envFile, $envContent);
    }
} 