<?php

namespace App\Services\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RouteGenerator
{
    protected $modelName;
    protected $controllerNamespace;
    protected $apiControllerNamespace;
    protected $routeName;

    public function __construct($modelName, $controllerNamespace = 'App\Http\Controllers', $apiControllerNamespace = 'App\Http\Controllers\Api')
    {
        $this->modelName = $modelName;
        $this->controllerNamespace = $controllerNamespace;
        $this->apiControllerNamespace = $apiControllerNamespace;
        $this->routeName = Str::kebab(Str::plural($this->modelName));
    }

    public function generate()
    {
        $controller = $this->modelName . 'Controller';

        // Generate API Route with API namespace
        $apiControllerPath = $this->apiControllerNamespace . '\\' . $controller;
        $apiRoute = <<<ROUTE
        Route::prefix('api')->group(function () {
            Route::apiResource('{$this->routeName}', \\{$apiControllerPath}::class)
                ->names('api.{$this->routeName}')
                ->parameters(['{$this->routeName}' => '{$this->modelNameLowerCase()}']);
        });
        ROUTE;
        $this->appendToRoutesFile($apiRoute, 'api');

        // Generate Web Route with standard namespace
        $webControllerPath = $this->controllerNamespace . '\\' . $controller;
        $webRoute = <<<ROUTE
        Route::resource('{$this->routeName}', \\{$webControllerPath}::class)
            ->parameters(['{$this->routeName}' => '{$this->modelNameLowerCase()}']);
        ROUTE;
        $this->appendToRoutesFile($webRoute, 'web');
    }

    protected function appendToRoutesFile($route, $type)
    {
        $path = base_path("routes/{$type}.php");

        // Create the file if it doesn't exist
        if (!File::exists($path)) {
            $content = "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n";
            File::put($path, $content);
        } else {
            $content = File::get($path);
        }

        // Check if route already exists
        if (strpos($content, "Route::resource('{$this->routeName}'") === false &&
            strpos($content, "Route::apiResource('{$this->routeName}'") === false) {

            // Add proper route group if not exists
            if ($type === 'api' && strpos($content, 'Route::prefix(\'api\')') === false) {
                $route = "Route::prefix('api')->group(function () {\n    {$route}\n});";
            }

            $content = rtrim($content) . "\n\n" . $route . "\n";
            File::put($path, $content);
        }
    }

    protected function modelNameLowerCase()
    {
        return Str::camel($this->modelName);
    }
}
