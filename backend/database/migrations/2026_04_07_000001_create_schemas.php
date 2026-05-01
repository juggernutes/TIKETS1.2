<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $schemas = ['core', 'cat', 'hd', 'ped', 'rh', 'com'];

        foreach ($schemas as $schema) {
            DB::statement("IF NOT EXISTS (SELECT 1 FROM sys.schemas WHERE name = '{$schema}')
                           EXEC('CREATE SCHEMA {$schema}')");
        }
    }

    public function down(): void
    {
        // Los schemas no se eliminan en down() para evitar pérdida accidental de datos
    }
};
