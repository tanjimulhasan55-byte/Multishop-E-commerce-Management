<?php

namespace App\Support\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Translatable attributes are stored as JSON ({"en": "...", "bn": "..."}).
 * JSON values cannot be indexed directly, so we add a STORED generated
 * column holding the English value. It backs indexed sorting, prefix search
 * and (on MySQL) FULLTEXT search, and is never written by the application.
 */
trait TranslatableColumns
{
    protected function englishShadowColumn(Blueprint $table, string $column): void
    {
        $expression = match (Schema::getConnection()->getDriverName()) {
            'mysql', 'mariadb' => "json_unquote(json_extract(`{$column}`, '$.en'))",
            'pgsql' => "({$column}->>'en')",
            default => "json_extract({$column}, '$.en')",
        };

        $table->string("{$column}_en", 191)->storedAs($expression)->nullable();
    }

    protected function supportsFullText(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
}
