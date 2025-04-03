<?php

namespace App\Services\CrudGenerator;

use Illuminate\Support\Facades\File;

class RequestGenerator
{
    protected $modelName;
    protected $fields;

    public function __construct($modelName, $fields)
    {
        $this->modelName = $modelName;
        $this->fields = $fields;
    }

    public function generate()
    {
        $stub = File::get($this->getStubPath('request'));

        $replacements = [
            '{{namespace}}' => 'App\\Http\\Requests',
            '{{class}}' => $this->modelName . 'Request',
            '{{rules}}' => $this->generateRules(),
        ];

        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        $path = app_path('Http/Requests/' . $this->modelName . 'Request.php');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
    }

    protected function generateRules()
    {
        $rules = [];

        foreach ($this->fields as $fieldName => $fieldType) {
            $ruleParts = [];

            // Required rule for most fields
            if (!in_array($fieldName, ['id', 'created_at', 'updated_at'])) {
                $ruleParts[] = 'required';
            }

            // Type-specific rules
            if (is_array($fieldType) && $fieldType['type'] === 'enum') {
                $options = implode(',', array_map('trim', $fieldType['options']));
                $ruleParts[] = "in:{$options}";
            } else {
                switch ($fieldType) {
                    case 'string':
                        $ruleParts[] = 'string';
                        $ruleParts[] = 'max:255';
                        break;
                    case 'text':
                        $ruleParts[] = 'string';
                        break;
                    case 'integer':
                        $ruleParts[] = 'integer';
                        break;
                    case 'boolean':
                        $ruleParts[] = 'boolean';
                        break;
                    case 'date':
                        $ruleParts[] = 'date';
                        break;
                    case 'datetime':
                        $ruleParts[] = 'date';
                        break;
                }
            }

            $rules[$fieldName] = implode('|', $ruleParts);
        }

        $ruleStrings = [];
        foreach ($rules as $field => $rule) {
            $ruleStrings[] = "'{$field}' => '{$rule}'";
        }

        return implode(",\n            ", $ruleStrings);
    }

    protected function getStubPath($type)
    {
        return base_path("stubs/{$type}.stub");
    }
}
