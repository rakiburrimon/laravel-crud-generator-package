<?php

namespace App\Services\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ViewGenerator
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
        $views = ['index', 'create', 'edit', 'show'];

        foreach ($views as $view) {
            $stub = File::get($this->getStubPath("view-{$view}"));

            $replacements = [
                '{{model}}' => $this->modelName,
                '{{modelVariable}}' => Str::camel($this->modelName),
                '{{modelPlural}}' => Str::camel(Str::plural($this->modelName)),
                '{{fields}}' => $this->generateFieldsForView($view),
                '{{formFields}}' => $this->generateFormFields(),
            ];

            $content = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $stub
            );

            $path = resource_path("views/" . Str::kebab(Str::plural($this->modelName)) . "/{$view}.blade.php");

            File::ensureDirectoryExists(dirname($path));
            File::put($path, $content);
        }
    }

    protected function generateFieldsForView($viewType)
    {
        $fieldsHtml = '';

        foreach ($this->fields as $fieldName => $fieldType) {
            if ($viewType === 'index') {
                $fieldsHtml .= "<td>{{ \${$fieldName} }}</td>\n            ";
            } elseif ($viewType === 'show') {
                $fieldsHtml .= "<div class=\"form-group\">\n";
                $fieldsHtml .= "    <label>{$fieldName}:</label>\n";
                $fieldsHtml .= "    <p>{{ \$" . Str::camel($this->modelName) . "->{$fieldName} }}</p>\n";
                $fieldsHtml .= "</div>\n        ";
            }
        }

        return $fieldsHtml;
    }

    protected function generateFormFields()
    {
        $fieldsHtml = '';

        foreach ($this->fields as $fieldName => $fieldType) {
            if (in_array($fieldName, ['id', 'created_at', 'updated_at'])) {
                continue;
            }

            $fieldsHtml .= "<div class=\"form-group\">\n";
            $fieldsHtml .= "    <label for=\"{$fieldName}\">" . ucfirst($fieldName) . ":</label>\n";

            if (is_array($fieldType) && $fieldType['type'] === 'enum') {
                $fieldsHtml .= "    <select name=\"{$fieldName}\" id=\"{$fieldName}\" class=\"form-control\">\n";
                foreach ($fieldType['options'] as $option) {
                    $option = trim($option);
                    $fieldsHtml .= "        <option value=\"{$option}\" {{ old('{$fieldName}', \$" . Str::camel($this->modelName) . "->{$fieldName} ?? null) == '{$option}' ? 'selected' : '' }}>" . ucfirst($option) . "</option>\n";
                }
                $fieldsHtml .= "    </select>\n";
            } elseif ($fieldType === 'text') {
                $fieldsHtml .= "    <textarea name=\"{$fieldName}\" id=\"{$fieldName}\" class=\"form-control\">{{ old('{$fieldName}', \$" . Str::camel($this->modelName) . "->{$fieldName} ?? null) }}</textarea>\n";
            } else {
                $inputType = $this->getInputType($fieldType);
                $fieldsHtml .= "    <input type=\"{$inputType}\" name=\"{$fieldName}\" id=\"{$fieldName}\" class=\"form-control\" value=\"{{ old('{$fieldName}', \$" . Str::camel($this->modelName) . "->{$fieldName} ?? null) }}\">\n";
            }

            $fieldsHtml .= "    @error('{$fieldName}')\n";
            $fieldsHtml .= "        <div class=\"text-danger\">{{ \$message }}</div>\n";
            $fieldsHtml .= "    @enderror\n";
            $fieldsHtml .= "</div>\n\n        ";
        }

        return $fieldsHtml;
    }

    protected function getInputType($fieldType)
    {
        if (is_array($fieldType)) {
            return 'text';
        }

        switch ($fieldType) {
            case 'integer':
                return 'number';
            case 'boolean':
                return 'checkbox';
            case 'date':
                return 'date';
            case 'datetime':
                return 'datetime-local';
            case 'email':
                return 'email';
            case 'password':
                return 'password';
            default:
                return 'text';
        }
    }

    protected function getStubPath($type)
    {
        return base_path("stubs/{$type}.stub");
    }
}
