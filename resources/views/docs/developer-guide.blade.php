# Bail Mobilite Platform Developer Documentation

This document provides technical information for developers working on the Bail Mobilite Platform.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Code Structure](#code-structure)
3. [Development Environment](#development-environment)
4. [API Development](#api-development)
5. [Frontend Development](#frontend-development)
6. [Database Schema](#database-schema)
7. [Testing](#testing)
8. [Deployment](#deployment)
9. [Contributing](#contributing)
10. [Best Practices](#best-practices)

## Architecture Overview

### System Components

The platform follows a modern MVC architecture with additional layers for specific functionality:

```
┌─────────────────────────────────────┐
│           Presentation             │
├─────────────────────────────────────┤
│           Application               │
├─────────────────────────────────────┤
│           Business Logic            │
├─────────────────────────────────────┤
│           Data Access               │
├─────────────────────────────────────┤
│           Infrastructure            │
└─────────────────────────────────────┘
```

### Key Technologies

**Backend:**
- Laravel 11.x Framework
- PHP 8.2+
- MySQL 8.0+ Database
- Redis for Caching and Sessions
- Laravel Horizon for Queue Management

**Frontend:**
- Blade Templates
- Tailwind CSS 3.x
- Alpine.js for Interactivity
- Vite for Asset Building

**Infrastructure:**
- Docker for Containerization
- Nginx as Web Server
- Supervisor for Process Management

## Code Structure

### Directory Layout

```
app/
├── Console/           # Artisan commands
├── Exceptions/         # Custom exceptions
├── Http/
│   ├── Controllers/   # MVC controllers
│   ├── Middleware/     # HTTP middleware
│   └── Requests/      # Form request validation
├── Models/            # Eloquent models
├── Providers/         # Service providers
├── Services/         # Business logic services
└── Traits/           # Reusable traits

config/                # Configuration files
database/
├── factories/         # Model factories
├── migrations/       # Database migrations
└── seeds/            # Database seeders

public/               # Public web root
resources/
├── views/            # Blade templates
├── css/              # CSS source files
└── js/               # JavaScript source files

routes/               # Route definitions
storage/              # File storage
tests/                # Automated tests
```

### Key Files

**Main Entry Points:**
- `app/Providers/AppServiceProvider.php` - Main service provider
- `bootstrap/app.php` - Application bootstrap
- `config/app.php` - Main configuration
- `routes/web.php` - Web routes
- `routes/api.php` - API routes

## Development Environment

### Setting Up Local Development

1. **Clone Repository:**
```bash
git clone https://github.com/your-organization/bail-mobilite.git
cd bail-mobilite
```

2. **Install Dependencies:**
```bash
composer install
npm install
```

3. **Configure Environment:**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database Setup:**
```bash
# Start database service (Docker or native)
php artisan migrate
php artisan db:seed
```

5. **Start Development Servers:**
```bash
# Terminal 1: Start PHP development server
php artisan serve

# Terminal 2: Start Vite development server
npm run dev
```

### Docker Development Environment

**Building Development Containers:**
```bash
docker-compose -f docker-compose.dev.yml up -d
```

**Accessing Containers:**
```bash
# Access application container
docker-compose exec app bash

# Access database container
docker-compose exec mysql bash
```

### IDE Configuration

**Recommended IDEs:**
- PHPStorm
- Visual Studio Code
- Sublime Text

**Essential Extensions:**
- PHP Intelephense
- Blade formatter
- Tailwind CSS IntelliSense
- ESLint
- Prettier

**Code Formatting:**
Configure your IDE to use PSR-12 coding standards with:
- Tab size: 4 spaces
- Line width: 120 characters
- Remove trailing whitespace

## API Development

### REST API Principles

**Resource Naming:**
- Use plural nouns for collections (`/api/v1/missions`)
- Use singular nouns for specific resources (`/api/v1/missions/1`)
- Use hyphens for multi-word resources (`/api/v1/checklist-items`)

**HTTP Methods:**
- `GET` - Retrieve resources
- `POST` - Create resources
- `PUT` - Update entire resources
- `PATCH` - Partially update resources
- `DELETE` - Remove resources

**Response Format:**
```json
{
  "data": {...},
  "meta": {...},
  "links": {...}
}
```

### Creating New API Endpoints

1. **Define Route:**
```php
// routes/api.php
Route::apiResource('properties', PropertyController::class);
```

2. **Create Controller:**
```php
// app/Http/Controllers/Api/PropertyController.php
class PropertyController extends Controller
{
    public function index()
    {
        $properties = Property::paginate(15);
        return PropertyResource::collection($properties);
    }
    
    public function store(PropertyRequest $request)
    {
        $property = Property::create($request->validated());
        return new PropertyResource($property);
    }
}
```

3. **Create Request Validation:**
```php
// app/Http/Requests/PropertyRequest.php
class PropertyRequest extends FormRequest
{
    public function rules()
    {
        return [
            'property_address' => 'required|string|max:255',
            'property_type' => 'required|string|in:apartment,house,commercial',
        ];
    }
}
```

4. **Create API Resource:**
```php
// app/Http/Resources/PropertyResource.php
class PropertyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'property_address' => $this->property_address,
            'property_type' => $this->property_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### API Documentation

Document all API endpoints using OpenAPI/Swagger annotations:

```php
/**
 * @OA\Get(
 *     path="/api/v1/properties",
 *     summary="Get list of properties",
 *     tags={"Properties"},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Property")),
 *             @OA\Property(property="meta", ref="#/components/schemas/Meta"),
 *             @OA\Property(property="links", ref="#/components/schemas/Links")
 *         )
 *     )
 * )
 */
public function index()
{
    // Implementation
}
```

## Frontend Development

### Blade Templates

**Template Inheritance:**
Use template inheritance for consistent layouts:

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>@yield('title', 'Bail Mobilite')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('layouts.navigation')
    
    <main>
        @yield('content')
    </main>
    
    @stack('scripts')
</body>
</html>
```

**Section Usage:**
```blade
{{-- resources/views/properties/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Properties')

@section('content')
    <h1>Properties</h1>
    {{-- Content --}}
@endsection

@push('scripts')
    <script>
        // Page-specific JavaScript
    </script>
@endpush
```

### Component-Based Development

**Creating Blade Components:**
```bash
php artisan make:component PropertyCard
```

```php
// app/View/Components/PropertyCard.php
class PropertyCard extends Component
{
    public function __construct(
        public Property $property,
        public bool $compact = false
    ) {}
    
    public function render()
    {
        return view('components.property-card');
    }
}
```

```blade
{{-- resources/views/components/property-card.blade.php --}}
<div class="property-card bg-white rounded-lg shadow-md p-4">
    <h3 class="text-lg font-semibold">{{ $property->property_address }}</h3>
    <p class="text-gray-600">{{ $property->property_type }}</p>
    
    @if(!$compact)
        <div class="mt-2">
            <a href="{{ route('properties.show', $property) }}" class="text-blue-600 hover:underline">
                View Details
            </a>
        </div>
    @endif
</div>
```

**Using Components:**
```blade
<x-property-card :property="$property" />
<x-property-card :property="$property" compact />
```

### Alpine.js Integration

**Simple Interactivity:**
```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">
        Content that can be toggled
    </div>
</div>
```

**Complex Components:**
```javascript
// resources/js/components/mission-form.js
export default function missionForm() {
    return {
        mission: {
            title: '',
            description: '',
            property_address: '',
            checkin_date: '',
            checkout_date: ''
        },
        
        async submit() {
            try {
                const response = await fetch('/api/missions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.mission)
                });
                
                if (response.ok) {
                    window.location.href = '/missions';
                }
            } catch (error) {
                console.error('Submission failed:', error);
            }
        }
    }
}
```

## Database Schema

### Core Tables

**Users Table:**
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'ops', 'checker') NOT NULL DEFAULT 'checker',
    email_verified_at TIMESTAMP NULL,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    preferences JSON NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Missions Table:**
```sql
CREATE TABLE missions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    property_address TEXT NOT NULL,
    checkin_date DATE NOT NULL,
    checkout_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    checker_id BIGINT UNSIGNED NOT NULL,
    ops_id BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (checker_id) REFERENCES users(id),
    FOREIGN KEY (ops_id) REFERENCES users(id),
    FOREIGN KEY (admin_id) REFERENCES users(id)
);
```

**Checklists Table:**
```sql
CREATE TABLE checklists (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mission_id BIGINT UNSIGNED NOT NULL,
    type ENUM('checkin', 'checkout') NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'submitted') DEFAULT 'pending',
    signature_path VARCHAR(255) NULL,
    submitted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
);
```

### Relationship Diagram

```
Users (1) ←→ (n) Missions
Users (1) ←→ (n) Checklists (through Missions)
Missions (1) ←→ (n) Checklists
Checklists (1) ←→ (n) ChecklistItems
ChecklistItems (n) ←→ (1) Amenities
Amenities (1) ←→ (n) AmenityTypes
```

### Database Migrations

**Creating Migrations:**
```bash
php artisan make:migration create_properties_table
```

**Writing Migrations:**
```php
// database/migrations/xxxx_xx_xx_xxxxxx_create_properties_table.php
class CreatePropertiesTable extends Migration
{
    public function up()
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('property_address');
            $table->string('property_type');
            $table->string('owner_name')->nullable();
            $table->string('owner_contact')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('property_type');
            $table->index(['property_type', 'created_at']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('properties');
    }
}
```

**Running Migrations:**
```bash
# Run all pending migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Reset and re-run all migrations
php artisan migrate:refresh
```

## Testing

### Test Organization

```
tests/
├── Unit/              # Unit tests
│   ├── Http/          # HTTP tests
│   ├── Models/        # Model tests
│   ├── Services/       # Service tests
│   └── Jobs/          # Job tests
├── Feature/           # Feature/integration tests
│   ├── Auth/          # Authentication tests
│   ├── Api/           # API tests
│   └── Console/       # Console command tests
└── Pest.php           # Pest configuration
```

### Writing Unit Tests

**Model Tests:**
```php
// tests/Unit/Models/UserTest.php
use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    public function test_user_can_be_created()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'checker'
        ]);
        
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'checker'
        ]);
    }
    
    public function test_user_has_correct_role()
    {
        $user = User::factory()->create(['role' => 'admin']);
        
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('checker'));
    }
}
```

**Service Tests:**
```php
// tests/Unit/Services/MissionServiceTest.php
use Tests\TestCase;
use App\Services\MissionService;
use App\Models\User;
use App\Models\Mission;

class MissionServiceTest extends TestCase
{
    private MissionService $missionService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->missionService = new MissionService();
    }
    
    public function test_mission_can_be_created()
    {
        $ops = User::factory()->create(['role' => 'ops']);
        $checker = User::factory()->create(['role' => 'checker']);
        
        $mission = $this->missionService->createMission([
            'title' => 'Test Mission',
            'description' => 'Test Description',
            'property_address' => '123 Test St',
            'checkin_date' => '2023-06-01',
            'checkout_date' => '2023-06-30',
            'ops_id' => $ops->id,
            'checker_id' => $checker->id
        ]);
        
        $this->assertInstanceOf(Mission::class, $mission);
        $this->assertEquals('Test Mission', $mission->title);
    }
}
```

### Writing Feature Tests

**API Tests:**
```php
// tests/Feature/Api/MissionApiTest.php
use Tests\TestCase;
use App\Models\User;
use App\Models\Mission;

class MissionApiTest extends TestCase
{
    public function test_authenticated_user_can_get_missions()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Mission::factory()->count(3)->create();
        
        $response = $this->actingAs($user)
            ->getJson('/api/v1/missions');
            
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'status']
                ],
                'meta',
                'links'
            ])
            ->assertJsonCount(3, 'data');
    }
    
    public function test_unauthorized_user_cannot_create_mission()
    {
        $user = User::factory()->create(['role' => 'checker']);
        
        $response = $this->actingAs($user)
            ->postJson('/api/v1/missions', [
                'title' => 'Unauthorized Mission'
            ]);
            
        $response->assertStatus(403);
    }
}
```

### Running Tests

**Test Commands:**
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Api/MissionApiTest.php

# Run tests with coverage
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel

# Run tests with specific group
php artisan test --group=integration
```

**Continuous Integration:**
Configure CI to run tests automatically:
```yaml
# .github/workflows/test.yml
name: Run Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: php artisan test --parallel
```

## Deployment

### Deployment Pipeline

**CI/CD Workflow:**
1. **Code Review:** Pull requests require approval
2. **Automated Testing:** Run full test suite
3. **Security Scanning:** Check for vulnerabilities
4. **Build Assets:** Compile frontend assets
5. **Deploy to Staging:** Test in staging environment
6. **Deploy to Production:** Promote to production

**Deployment Script:**
```bash
#!/bin/bash
# deploy.sh

# Pull latest code
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader
npm install --production
npm run build

# Run migrations
php artisan migrate --force

# Clear and cache configurations
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo supervisorctl restart horizon
sudo systemctl reload nginx
```

### Environment-Specific Configuration

**Production Configuration:**
```env
# .env.production
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
DB_CONNECTION=mysql
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

**Staging Configuration:**
```env
# .env.staging
APP_ENV=staging
APP_DEBUG=true
LOG_LEVEL=debug
DB_CONNECTION=mysql_staging
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Rollback Procedures

**Quick Rollback:**
```bash
# Rollback to previous deployment
git checkout HEAD~1
composer install --no-dev
php artisan config:cache
sudo systemctl reload nginx
```

**Database Rollback:**
```bash
# Rollback database migrations
php artisan migrate:rollback --step=1
```

**Complete Rollback:**
```bash
# Restore from backup
mysql -u user -p database < backup.sql
git checkout stable-tag
composer install --no-dev
php artisan config:cache
sudo systemctl reload nginx
```

## Contributing

### Git Workflow

**Branching Strategy:**
- `main` - Production code
- `develop` - Development branch
- `feature/*` - Feature branches
- `hotfix/*` - Hotfix branches
- `release/*` - Release branches

**Commit Messages:**
Follow conventional commit format:
```
feat: add new property management feature
fix: resolve mission creation validation error
docs: update API documentation
test: add mission service unit tests
refactor: optimize checklist item processing
```

### Code Review Process

**Pull Request Requirements:**
1. **Description:** Clear explanation of changes
2. **Tests:** Include relevant tests
3. **Documentation:** Update documentation as needed
4. **Code Quality:** Follow coding standards
5. **Security:** Consider security implications

**Review Criteria:**
- Code correctness and efficiency
- Adherence to coding standards
- Proper error handling
- Security considerations
- Performance impact
- Test coverage

### Coding Standards

**PSR Compliance:**
- PSR-1: Basic Coding Standard
- PSR-2: Coding Style Guide
- PSR-4: Autoloading Standard
- PSR-12: Extended Coding Style Guide

**Naming Conventions:**
```php
// Classes: PascalCase
class MissionService {}

// Methods: camelCase
public function createMission() {}

// Constants: UPPER_SNAKE_CASE
const DEFAULT_STATUS = 'pending';

// Variables: camelCase
$missionStatus = 'approved';
```

## Best Practices

### Performance Optimization

**Database Optimization:**
```php
// Use eager loading to prevent N+1 queries
$masters = Master::with('detail')->get();

// Use select() to limit columns
$users = User::select('id', 'name', 'email')->get();

// Use chunk() for large datasets
User::chunk(1000, function ($users) {
    // Process users in chunks
});
```

**Caching Strategies:**
```php
// Cache expensive queries
$popularMissions = Cache::remember('popular_missions', 3600, function () {
    return Mission::where('status', 'completed')
        ->orderBy('created_at', 'desc')
        ->take(10)
        ->get();
});

// Cache computed values
$totalRevenue = Cache::remember('total_revenue', 1800, function () {
    return Order::sum('amount');
});
```

### Security Best Practices

**Input Validation:**
```php
// Use Form Requests for validation
class MissionRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'checkin_date' => 'required|date|after:today',
            'checker_id' => 'required|exists:users,id,role,checker'
        ];
    }
}
```

**Authorization:**
```php
// Use policies for complex authorization
class MissionPolicy
{
    public function update(User $user, Mission $mission)
    {
        return $user->id === $mission->ops_id || 
               $user->hasRole('admin');
    }
}

// Apply policies in controllers
public function update(MissionRequest $request, Mission $mission)
{
    $this->authorize('update', $mission);
    // Update logic
}
```

### Error Handling

**Graceful Error Handling:**
```php
// Handle exceptions appropriately
try {
    $mission = $this->missionService->createMission($data);
    return new MissionResource($mission);
} catch (ValidationException $e) {
    return response()->json([
        'message' => 'Validation failed',
        'errors' => $e->errors()
    ], 422);
} catch (Exception $e) {
    Log::error('Mission creation failed', [
        'data' => $data,
        'error' => $e->getMessage()
    ]);
    
    return response()->json([
        'message' => 'Failed to create mission'
    ], 500);
}
```

### Logging

**Structured Logging:**
```php
// Log important events with context
Log::info('Mission created', [
    'mission_id' => $mission->id,
    'checker_id' => $mission->checker_id,
    'ops_id' => $mission->ops_id,
    'user_id' => auth()->id()
]);

// Log errors with full context
Log::error('Mission creation failed', [
    'input_data' => $request->all(),
    'user_id' => auth()->id(),
    'error_message' => $e->getMessage(),
    'stack_trace' => $e->getTraceAsString()
]);
```

---

*This developer documentation should be updated regularly to reflect changes in the codebase. Last updated: {{ date('Y-m-d') }}*

For development inquiries, contact: dev@bailmobilite.com