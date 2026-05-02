<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class SqlServerExists implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly string $column,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!DB::table($this->table)->where($this->column, $value)->exists()) {
            $fail('El valor seleccionado no existe.');
        }
    }
}
