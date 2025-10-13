<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModule extends Command
{
    protected $signature = 'make:module {name : The name of the module}';
    protected $description = 'Create a new module with standard structure';

    public function handle()
    {
        $moduleName = $this->argument('name');
        $singularName = Str::singular($moduleName);
        $pluralName = Str::plural($moduleName);

        // Standardize naming
        $studlySingular = Str::studly($singularName);
        $studlyPlural = Str::studly($pluralName);
        $camelSingular = Str::camel($singularName);
        $snakePlural = Str::snake($pluralName);

        // Create directory structure
        $basePath = app_path("Modules/{$studlyPlural}");

        $directories = [
            'Http/Controllers',
            'Http/Middleware',
            'Http/Requests',
            'Http/Resources',
            'Models',
            'Providers',
            'Database/Migrations',
            'Database/Factories',
            'Database/Seeders',
            'Services',
            'Events',
            'Listeners',
            'Tests',
        ];

        $this->info('Creating module directories...');
        foreach ($directories as $directory) {
            File::makeDirectory("{$basePath}/{$directory}", 0755, true, true);
            $this->line("Created: {$basePath}/{$directory}");
        }

        // Create files
        $this->info('Creating module files...');

        // Create Service Provider
        $this->createServiceProvider($basePath, $studlyPlural, $studlySingular);

        // Create Model
        $this->createModel($basePath, $studlyPlural, $studlySingular);

        // Create Controller
        $this->createController($basePath, $studlyPlural, $studlySingular);

        // Create Form Request
        $this->createRequest($basePath, $studlyPlural, $studlySingular);

        // Create Resource
        $this->createResource($basePath, $studlyPlural, $studlySingular);

        // Create Migration
        $this->createMigration($basePath, $studlyPlural, $snakePlural);

        // Create Factory
        $this->createFactory($basePath, $studlyPlural, $studlySingular);

        // Create example test
        $this->createTest($basePath, $studlyPlural, $studlySingular);

        // Create or update routes
        $this->updateRoutes($studlyPlural, $studlySingular, $camelSingular);

        $this->info('Module setup completed successfully!');
        $this->info("Don't forget to register {$studlyPlural}ServiceProvider in AppServiceProvider if not using auto-discovery.");
    }

    /**
     * Create the service provider file.
     */
    protected function createServiceProvider($basePath, $studlyPlural, $studlySingular)
    {
        $stub = <<<EOT
<?php

namespace App\Modules\\{$studlyPlural}\Providers;

use Illuminate\Support\ServiceProvider;

class {$studlyPlural}ServiceProvider extends ServiceProvider
{
    /**
     * Register any module services.
     */
    public function register(): void
    {
        // Register module-specific bindings
    }

    /**
     * Bootstrap any module services.
     */
    public function boot(): void
    {
        // Load module migrations
        \$this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
EOT;

        File::put("{$basePath}/Providers/{$studlyPlural}ServiceProvider.php", $stub);
        $this->line("Created: {$basePath}/Providers/{$studlyPlural}ServiceProvider.php");
    }

    /**
     * Create the model file.
     */
    protected function createModel($basePath, $studlyPlural, $studlySingular)
    {
        $stub = <<<EOT
<?php

namespace App\Modules\\{$studlyPlural}\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static paginate()
 * @method static create(mixed \$validated)
 */

class {$studlySingular} extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected \$fillable = [
        // Define your fillable attributes here
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected \$casts = [
        // Define your attribute casts here
    ];
}
EOT;

        File::put("{$basePath}/Models/{$studlySingular}.php", $stub);
        $this->line("Created: {$basePath}/Models/{$studlySingular}.php");
    }

    /**
     * Create the controller file.
     */
    protected function createController($basePath, $studlyPlural, $studlySingular)
    {
        $stub = <<<EOT
<?php

namespace App\Modules\\{$studlyPlural}\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\\{$studlyPlural}\Models\\{$studlySingular};
use App\Modules\\{$studlyPlural}\Http\Requests\\{$studlySingular}Request;
use App\Modules\\{$studlyPlural}\Http\Resources\\{$studlySingular}Resource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class {$studlySingular}Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        \$query = {$studlySingular}::query();

        // If you need to search by specific fields
        if (\$name = request('name')) {
            \$query->where('name', 'like', "%{\$name}%");
        }
        // if the get parameter raw is present, return all locations without pagination
        if (request()->has('raw')) {
            \${$studlyPlural} = \$query->get();
            return {$studlySingular}Resource::collection(\${$studlyPlural});
        }

        \${$studlyPlural} = \$query->paginate();
        return {$studlySingular}Resource::collection(\${$studlyPlural});
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store({$studlySingular}Request \$request): {$studlySingular}Resource
    {
        \${$studlySingular} = {$studlySingular}::create(\$request->validated());
        return new {$studlySingular}Resource(\${$studlySingular});
    }

    /**
     * Display the specified resource.
     */
    public function show({$studlySingular} \${$studlySingular}): {$studlySingular}Resource
    {
        return new {$studlySingular}Resource(\${$studlySingular});
    }

    /**
     * Update the specified resource in storage.
     */
    public function update({$studlySingular}Request \$request, {$studlySingular} \${$studlySingular}): {$studlySingular}Resource
    {
        \${$studlySingular}->update(\$request->validated());
        return new {$studlySingular}Resource(\${$studlySingular});
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy({$studlySingular} \${$studlySingular}): Response
    {
        \${$studlySingular}->delete();
        return response()->noContent();
    }
}
EOT;

        File::put("{$basePath}/Http/Controllers/{$studlySingular}Controller.php", $stub);
        $this->line("Created: {$basePath}/Http/Controllers/{$studlySingular}Controller.php");
    }

    /**
     * Create the request file.
     */
    protected function createRequest($basePath, $studlyPlural, $studlySingular)
    {
        $stub = <<<EOT
<?php

namespace App\Modules\\{$studlyPlural}\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {$studlySingular}Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Define your validation rules here
        ];
    }
}
EOT;

        File::put("{$basePath}/Http/Requests/{$studlySingular}Request.php", $stub);
        $this->line("Created: {$basePath}/Http/Requests/{$studlySingular}Request.php");
    }

    /**
     * Create the resource file.
     */
    protected function createResource($basePath, $studlyPlural, $studlySingular)
    {
        $stub = <<<EOT
<?php

namespace App\Modules\\{$studlyPlural}\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {$studlySingular}Resource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request \$request
     * @return array
     */
    public function toArray(Request \$request): array
    {
        return [
            'id' => \$this->id,
            // Add your resource fields here
            'created_at' => \$this->created_at,
            'updated_at' => \$this->updated_at,
        ];
    }
}
EOT;

        File::put("{$basePath}/Http/Resources/{$studlySingular}Resource.php", $stub);
        $this->line("Created: {$basePath}/Http/Resources/{$studlySingular}Resource.php");
    }

    /**
     * Create the migration file.
     */
    protected function createMigration($basePath, $studlyPlural, $snakePlural)
    {
        $timestamp = date('Y_m_d_His');
        $stub = <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$snakePlural}', function (Blueprint \$table) {
            \$table->id();
            // Add your columns here
            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$snakePlural}');
    }
};
EOT;

        File::put("{$basePath}/Database/Migrations/{$timestamp}_create_{$snakePlural}_table.php", $stub);
        $this->line("Created: {$basePath}/Database/Migrations/{$timestamp}_create_{$snakePlural}_table.php");
    }

    /**
     * Create the factory file.
     */
    protected function createFactory($basePath, $studlyPlural, $studlySingular)
    {
        $stub = <<<EOT
<?php

namespace App\Modules\\{$studlyPlural}\Database\Factories;

use App\Modules\\{$studlyPlural}\Models\\{$studlySingular};
use Illuminate\Database\Eloquent\Factories\Factory;

class {$studlySingular}Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected \$model = {$studlySingular}::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            // Define your factory attributes here
        ];
    }
}
EOT;

        File::put("{$basePath}/Database/Factories/{$studlySingular}Factory.php", $stub);
        $this->line("Created: {$basePath}/Database/Factories/{$studlySingular}Factory.php");
    }

    /**
     * Create a test file.
     */
    protected function createTest($basePath, $studlyPlural, $studlySingular)
    {
        $stub = <<<EOT
<?php

namespace App\Modules\\{$studlyPlural}\Tests;

use Tests\TestCase;
use App\Modules\\{$studlyPlural}\Models\\{$studlySingular};
use Illuminate\Foundation\Testing\RefreshDatabase;

class {$studlySingular}Test extends TestCase
{
    use RefreshDatabase;

    /**
     * Test can fetch all records.
     */
    public function test_can_fetch_all_{$studlySingular}_records(): void
    {
        // Arrange
        {$studlySingular}::factory()->count(3)->create();

        // Act
        \$response = \$this->getJson('/api/{$studlySingular}');

        // Assert
        \$response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    // Add more tests as needed
}
EOT;

        File::put("{$basePath}/Tests/{$studlySingular}Test.php", $stub);
        $this->line("Created: {$basePath}/Tests/{$studlySingular}Test.php");
    }

    /**
     * Update or create routes.
     */
    protected function updateRoutes($studlyPlural, $studlySingular, $camelSingular)
    {
        // API Routes
        $apiRouteFile = base_path('routes/api.php');
        $apiRouteContent = File::get($apiRouteFile);

        $controllerNamespace = "App\\Modules\\{$studlyPlural}\\Http\\Controllers\\{$studlySingular}Controller";

        $newApiRoutes = <<<EOT

// {$studlyPlural} Module Routes
Route::prefix('{$camelSingular}')->group(function () {
    Route::get('/', [{$controllerNamespace}::class, 'index']);
    Route::post('/', [{$controllerNamespace}::class, 'store']);
    Route::get('/{$camelSingular}', [{$controllerNamespace}::class, 'show']);
    Route::put('/{$camelSingular}', [{$controllerNamespace}::class, 'update']);
    Route::delete('/{$camelSingular}', [{$controllerNamespace}::class, 'destroy']);
});
EOT;

        // Only add if the route doesn't already exist
        if (!Str::contains($apiRouteContent, "{$studlyPlural} Module Routes")) {
            File::append($apiRouteFile, $newApiRoutes);
            $this->line("Updated API routes with {$studlySingular} routes.");
        } else {
            $this->line("Routes for {$studlySingular} already exist in API routes file.");
        }
    }
}
