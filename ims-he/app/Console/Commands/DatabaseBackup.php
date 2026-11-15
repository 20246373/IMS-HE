<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Throwable;

class DatabaseBackup extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Create a timestamped database backup and keep the most recent backups.';

    public function handle(): int
    {
        $backupPath = (string) config('inventory.backup_path', storage_path('app/backups'));
        $keep = max(1, (int) config('inventory.backup_keep', 14));

        File::ensureDirectoryExists($backupPath);

        $connection = (string) config('database.default');
        $config = config("database.connections.{$connection}", []);
        $driver = $config['driver'] ?? $connection;

        $extension = in_array($driver, ['mysql', 'mariadb'], true) ? 'sql' : 'sqlite';
        $destination = $this->uniqueDestination($backupPath, $extension);

        try {
            $created = match ($driver) {
                'sqlite' => $this->backupSqlite($connection, $config, $destination),
                'mysql', 'mariadb' => $this->backupMysql($config, $destination),
                default => $this->unsupportedDriver($driver),
            };
        } catch (Throwable $exception) {
            if (File::exists($destination)) {
                File::delete($destination);
            }

            $this->error('Database backup failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        if (! $created) {
            if (File::exists($destination)) {
                File::delete($destination);
            }

            return self::FAILURE;
        }

        $removed = $this->pruneOldBackups($backupPath, $keep);

        AuditLog::record('Backed up', 'database');

        $this->info("Database backup created: {$destination}");

        if ($removed > 0) {
            $this->line("Removed {$removed} old backup(s).");
        }

        return self::SUCCESS;
    }

    /**
     * Copy the SQLite database file. In-memory databases (used by tests) have no
     * file to copy, so use SQLite's VACUUM INTO to write a real snapshot instead.
     */
    private function backupSqlite(string $connection, array $config, string $destination): bool
    {
        $source = (string) ($config['database'] ?? '');

        if ($source !== '' && $source !== ':memory:') {
            if (! preg_match('~^(?:[A-Za-z]:[\\\\/]|[\\\\/])~', $source)) {
                $candidate = base_path($source);

                if (is_file($candidate)) {
                    $source = $candidate;
                }
            }

            if (is_file($source)) {
                if (File::copy($source, $destination)) {
                    return true;
                }

                $this->error("Unable to copy the SQLite database from {$source}.");

                return false;
            }
        }

        try {
            $escapedDestination = str_replace("'", "''", $destination);
            $database = DB::connection($connection);
            $resumedTransactionLevels = 0;

            // VACUUM cannot run inside a transaction. The test database is an
            // in-memory connection wrapped by RefreshDatabase, so briefly commit
            // it, take the snapshot, and reopen the same number of transactions.
            if ($source === ':memory:' && $database->transactionLevel() > 0) {
                $resumedTransactionLevels = $database->transactionLevel();

                while ($database->transactionLevel() > 0) {
                    $database->commit();
                }
            }

            try {
                $database->statement("VACUUM INTO '{$escapedDestination}'");
            } finally {
                for ($level = 0; $level < $resumedTransactionLevels; $level++) {
                    $database->beginTransaction();
                }
            }

            if (File::exists($destination) && File::size($destination) > 0) {
                return true;
            }

            $this->error('SQLite did not create the backup file.');

            return false;
        } catch (Throwable $exception) {
            $this->error('Unable to snapshot the SQLite database: '.$exception->getMessage());

            return false;
        }
    }

    private function backupMysql(array $config, string $destination): bool
    {
        $database = (string) ($config['database'] ?? '');

        if ($database === '') {
            $this->error('No MySQL database name is configured.');

            return false;
        }

        $command = [
            'mysqldump',
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? '3306'),
            '--user='.($config['username'] ?? 'root'),
            '--single-transaction',
            '--skip-lock-tables',
            '--no-tablespaces',
        ];

        if (! empty($config['password'])) {
            $command[] = '--password='.$config['password'];
        }

        $command[] = $database;

        try {
            $result = Process::timeout(300)->run($command);
        } catch (Throwable $exception) {
            $this->error('mysqldump is not available: '.$exception->getMessage());

            return false;
        }

        if ($result->failed()) {
            $this->error('mysqldump failed: '.trim($result->errorOutput()));

            return false;
        }

        if (! File::put($destination, $result->output())) {
            $this->error('Unable to write the MySQL backup file.');

            return false;
        }

        return true;
    }

    private function unsupportedDriver(string $driver): bool
    {
        $this->error("Database backups are not supported for the [{$driver}] driver.");

        return false;
    }

    private function uniqueDestination(string $backupPath, string $extension): string
    {
        $directory = rtrim($backupPath, '\\/');
        $timestamp = now()->format('Ymd-His');
        $base = $directory.DIRECTORY_SEPARATOR."ims-he-backup-{$timestamp}";
        $destination = "{$base}.{$extension}";
        $suffix = 1;

        while (File::exists($destination)) {
            $destination = "{$base}-{$suffix}.{$extension}";
            $suffix++;
        }

        return $destination;
    }

    private function pruneOldBackups(string $backupPath, int $keep): int
    {
        $files = collect(File::files($backupPath))
            ->filter(fn ($file): bool => in_array(strtolower($file->getExtension()), ['sqlite', 'sql'], true))
            ->sortByDesc(fn ($file): string => $file->getFilename())
            ->values();

        $removed = 0;

        foreach ($files->slice($keep) as $file) {
            if (File::delete($file->getPathname())) {
                $removed++;
            }
        }

        return $removed;
    }
}
