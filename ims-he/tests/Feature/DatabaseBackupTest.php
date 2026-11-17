<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('app/testing/backup-'.uniqid());
        File::ensureDirectoryExists($this->directory);

        config([
            'inventory.backup_path' => $this->directory.'/backups',
            'inventory.backup_keep' => 14,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_backup_command_copies_the_sqlite_database_and_writes_an_audit_log(): void
    {
        $source = $this->directory.'/source.sqlite';
        File::put($source, 'sqlite database contents');

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $source,
        ]);

        $this->artisan('db:backup')->assertSuccessful();

        $backups = glob($this->directory.'/backups/ims-he-backup-*.sqlite');

        $this->assertCount(1, $backups);
        $this->assertSame('sqlite database contents', File::get($backups[0]));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Backed up',
            'table_name' => 'database',
        ]);
    }

    public function test_backup_command_keeps_only_the_configured_number_of_backups(): void
    {
        $source = $this->directory.'/source.sqlite';
        File::put($source, 'newest backup');

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $source,
            'inventory.backup_keep' => 2,
        ]);

        $backupDirectory = $this->directory.'/backups';
        File::ensureDirectoryExists($backupDirectory);
        File::put($backupDirectory.'/ims-he-backup-20260101-000000.sqlite', 'oldest backup');
        File::put($backupDirectory.'/ims-he-backup-20260102-000000.sqlite', 'older backup');
        File::put($backupDirectory.'/ims-he-backup-20260103-000000.sqlite', 'old backup');

        $this->artisan('db:backup')->assertSuccessful();

        $this->assertCount(2, glob($backupDirectory.'/ims-he-backup-*.sqlite'));
        $this->assertFileDoesNotExist($backupDirectory.'/ims-he-backup-20260101-000000.sqlite');
        $this->assertFileDoesNotExist($backupDirectory.'/ims-he-backup-20260102-000000.sqlite');
        $this->assertFileExists($backupDirectory.'/ims-he-backup-20260103-000000.sqlite');
    }

    public function test_backup_command_snapshots_an_in_memory_sqlite_database(): void
    {
        $this->artisan('db:backup')->assertSuccessful();

        $backups = glob($this->directory.'/backups/ims-he-backup-*.sqlite');

        $this->assertCount(1, $backups);
        $this->assertGreaterThan(0, File::size($backups[0]));
        $this->assertStringStartsWith('SQLite format 3', File::get($backups[0]));
    }
}
