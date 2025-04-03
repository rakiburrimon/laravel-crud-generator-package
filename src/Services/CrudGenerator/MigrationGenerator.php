<?php

namespace App\Services\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MigrationGenerator
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
        $stub = File::get($this->getStubPath('migration'));

        $tableName = Str::snake(Str::plural($this->modelName));
        $className = 'Create' . Str::plural($this->modelName) . 'Table';

        $replacements = [
            '{{class}}' => $className,
            '{{table}}' => $tableName,
            '{{columns}}' => $this->generateColumns(),
        ];

        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        $fileName = date('Y_m_d_His') . '_create_' . $tableName . '_table.php';
        $path = database_path('migrations/' . $fileName);

        File::put($path, $content);
    }

    protected function generateColumns()
    {
        $columns = [];

        foreach ($this->fields as $fieldName => $fieldType) {
            if (is_array($fieldType) && $fieldType['type'] === 'enum') {
                $options = implode("', '", array_map('trim', $fieldType['options']));
                $columns[] = "\$table->enum('{$fieldName}', ['{$options}'])";
            } else {
                $columns[] = "\$table->{$fieldType}('{$fieldName}')";
            }
        }

        // Add timestamps
        $columns[] = "\$table->timestamps()";

        return implode(";\n            ", $columns) . ';';
    }

    protected function getStubPath($type)
    {
        return base_path("stubs/{$type}.stub");
    }
}
