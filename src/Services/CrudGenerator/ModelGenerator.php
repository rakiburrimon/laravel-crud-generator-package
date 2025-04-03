<?php

namespace App\Services\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModelGenerator
{
    protected $modelName;
    protected $fields;
    protected $relations;

    public function __construct($modelName, $fields, $relations)
    {
        $this->modelName = $modelName;
        $this->fields = $fields;
        $this->relations = $relations;
    }

    public function generate()
    {
        $stub = File::get($this->getStubPath('model'));

        $replacements = [
            '{{namespace}}' => 'App\\Models',
            '{{class}}' => $this->modelName,
            '{{fillable}}' => $this->generateFillable(),
            '{{relations}}' => $this->generateRelations(),
            '{{imports}}' => $this->generateImports(),
        ];

        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        $path = app_path('Models/' . $this->modelName . '.php');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
    }

    protected function generateFillable()
    {
        $fillable = array_keys($this->fields);
        return "['" . implode("', '", $fillable) . "']";
    }

    protected function generateRelations()
    {
        if (empty($this->relations)) {
            return '';
        }

        $relationsCode = '';
        foreach ($this->relations as $relatedModel => $relationType) {
            $methodName = Str::camel(Str::plural($relatedModel));
            $relatedClass = Str::studly($relatedModel);

            switch ($relationType) {
                case 'hasMany':
                    $relationsCode .= "\n    public function {$methodName}()\n    {\n";
                    $relationsCode .= "        return \$this->hasMany({$relatedClass}::class);\n    }\n";
                    break;
                case 'belongsTo':
                    $relationsCode .= "\n    public function {$relatedModel}()\n    {\n";
                    $relationsCode .= "        return \$this->belongsTo({$relatedClass}::class);\n    }\n";
                    break;
                case 'belongsToMany':
                    $relationsCode .= "\n    public function {$methodName}()\n    {\n";
                    $relationsCode .= "        return \$this->belongsToMany({$relatedClass}::class);\n    }\n";
                    break;
            }
        }

        return $relationsCode;
    }

    protected function generateImports()
    {
        if (empty($this->relations)) {
            return '';
        }

        $imports = [];
        foreach (array_keys($this->relations) as $relatedModel) {
            $imports[] = 'use App\\Models\\' . Str::studly($relatedModel) . ';';
        }

        return implode("\n", array_unique($imports));
    }

    protected function getStubPath($type)
    {
        return base_path("stubs/{$type}.stub");
    }
}
