<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Pgvector\Laravel\Vector;

/**
 * SQLite-safe pgvector cast.
 *
 * The pgvector cast is correct for PostgreSQL, but SQLite tests store vector
 * columns as text. This cast keeps production behavior while allowing tests to
 * read/write vector attributes without requiring the pgvector extension.
 */
class PgVector implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        if (DB::getDriverName() === 'pgsql') {
            return new Vector($value);
        }

        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Vector) {
            return (string) $value;
        }

        if (is_array($value)) {
            return '['.implode(',', array_map('floatval', $value)).']';
        }

        return (string) $value;
    }
}
