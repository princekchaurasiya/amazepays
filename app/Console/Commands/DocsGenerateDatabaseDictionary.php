<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class DocsGenerateDatabaseDictionary extends Command
{
    protected $signature = 'docs:database-dictionary {--connection=mysql}';

    protected $description = 'Generate docs/DATABASE_DICTIONARY.md from information_schema';

    public function handle(): int
    {
        $connection = (string) $this->option('connection');
        $dbName = (string) config("database.connections.{$connection}.database");
        if ($dbName === '') {
            $this->error('Database name not configured for connection: '.$connection);
            return self::FAILURE;
        }

        $tables = DB::connection($connection)
            ->table('information_schema.tables')
            ->where('table_schema', $dbName)
            ->orderBy('table_name')
            ->pluck('table_name')
            ->all();

        $lines = [];
        $lines[] = '# DATABASE_DICTIONARY (generated)';
        $lines[] = '';
        $lines[] = 'Generated at: '.now()->toDateTimeString();
        $lines[] = '';

        foreach ($tables as $table) {
            $lines[] = "## {$table}";
            $lines[] = '';

            $cols = DB::connection($connection)
                ->table('information_schema.columns')
                ->where('table_schema', $dbName)
                ->where('table_name', $table)
                ->orderBy('ordinal_position')
                ->get(['column_name', 'data_type', 'is_nullable', 'column_default']);

            $lines[] = '```';
            foreach ($cols as $c) {
                $default = $c->column_default === null ? 'null' : (string) $c->column_default;
                $lines[] = sprintf(
                    '%s %s nullable=%s default=%s',
                    $c->column_name,
                    $c->data_type,
                    $c->is_nullable,
                    $default
                );
            }
            $lines[] = '```';
            $lines[] = '';
        }

        $path = base_path('docs/DATABASE_DICTIONARY.md');
        file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);

        $this->info("Wrote {$path}");

        return self::SUCCESS;
    }
}

