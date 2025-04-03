<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CrudGenerator\ModelGenerator;
use App\Services\CrudGenerator\MigrationGenerator;
use App\Services\CrudGenerator\ControllerGenerator;
use App\Services\CrudGenerator\RequestGenerator;
use App\Services\CrudGenerator\ViewGenerator;
use App\Services\CrudGenerator\RouteGenerator;

class MakeCrudCommand extends Command
{
    protected $signature = 'make:crud
                            {model : The name of the model}
                            {--fields= : Fields for the model (e.g., "name:string,description:text")}
                            {--relations= : Model relationships (e.g., "tasks:hasMany")}';

    protected $description = 'Generate a complete CRUD for a model';

    public function handle()
    {
        $modelName = $this->argument('model');
        $fields = $this->parseFields($this->option('fields'));
        $relations = $this->parseRelations($this->option('relations'));

        // Generate Model
        (new ModelGenerator($modelName, $fields, $relations))->generate();
        $this->info("Model generated successfully.");

        // Generate Migration
        (new MigrationGenerator($modelName, $fields))->generate();
        $this->info("Migration generated successfully.");

        // Generate Controller
        (new ControllerGenerator($modelName, $fields, $relations))->generate();
        $this->info("Controller generated successfully.");

        // Generate Request
        (new RequestGenerator($modelName, $fields))->generate();
        $this->info("Request generated successfully.");

        // Generate Views
        (new ViewGenerator($modelName, $fields))->generate();
        $this->info("Views generated successfully.");

        // Generate Routes
        (new RouteGenerator($modelName))->generate();
        $this->info("Routes generated successfully.");

        $this->info("CRUD for $modelName generated successfully!");
    }

    protected function parseFields($fieldsString)
    {
        if (empty($fieldsString)) {
            return [];
        }

        $fields = [];
        foreach (explode(',', $fieldsString) as $field) {
            $parts = explode(':', trim($field));
            $fieldName = $parts[0];
            $fieldType = $parts[1] ?? 'string';

            // Handle enum types
            if (str_starts_with($fieldType, 'enum(')) {
                $options = str_replace(['enum(', ')'], '', $fieldType);
                $fieldType = [
                    'type' => 'enum',
                    'options' => explode(',', $options)
                ];
            }

            $fields[$fieldName] = $fieldType;
        }

        return $fields;
    }

    protected function parseRelations($relationsString)
    {
        if (empty($relationsString)) {
            return [];
        }

        $relations = [];
        foreach (explode(',', $relationsString) as $relation) {
            $parts = explode(':', trim($relation));
            $relatedModel = $parts[0];
            $relationType = $parts[1] ?? 'hasMany';

            $relations[$relatedModel] = $relationType;
        }

        return $relations;
    }
}
