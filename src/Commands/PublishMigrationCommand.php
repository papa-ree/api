<?php

namespace Bale\Api\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PublishMigrationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:publish-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish migrations from Api with options (All, Auto, or Specific)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sourcePath = __DIR__.'/../../database/migrations/';

        if (! File::isDirectory($sourcePath)) {
            $this->error("Source directory not found: {$sourcePath}");

            return self::FAILURE;
        }

        $stubFiles = File::files($sourcePath);
        $migrations = [];

        foreach ($stubFiles as $file) {
            $filename = $file->getFilename();
            // Expected filename: migration_name.php.stub or migration_name.stub
            $baseName = Str::before($filename, '.stub');
            $baseName = Str::before($baseName, '.php');

            $migrations[$baseName] = $file->getPathname();
        }

        if (empty($migrations)) {
            $this->warn('No migrations found in the Api package.');

            return self::SUCCESS;
        }

        $this->info('Api Migration Publisher');

        $mode = $this->choice(
            'Select publishing mode:',
            ['All', 'Auto (Only missing)', 'Specific Migration'],
            1 // Default to Auto
        );

        switch ($mode) {
            case 'All':
                $this->publishAll($migrations);
                break;
            case 'Auto (Only missing)':
                $this->publishAuto($migrations);
                break;
            case 'Specific Migration':
                $this->publishSpecific($migrations);
                break;
        }

        $this->info('Publishing process completed.');

        return self::SUCCESS;
    }

    protected function publishAll(array $migrations): void
    {
        foreach ($migrations as $baseName => $path) {
            $this->publishFile($baseName, $path);
        }
    }

    protected function publishAuto(array $migrations): void
    {
        $published = 0;
        foreach ($migrations as $baseName => $path) {
            if (! $this->migrationExists($baseName)) {
                $this->publishFile($baseName, $path);
                $published++;
            } else {
                $this->line("Migration <info>{$baseName}</info> already exists. Skipping.");
            }
        }

        if ($published === 0) {
            $this->info('No new migrations to publish.');
        }
    }

    protected function publishSpecific(array $migrations): void
    {
        $options = array_keys($migrations);
        $selected = $this->choice(
            'Select migration to publish (comma separated for multiple):',
            $options,
            null,
            null,
            true // Multiple selection allowed
        );

        foreach ($selected as $baseName) {
            $this->publishFile($baseName, $migrations[$baseName]);
        }
    }

    protected function migrationExists(string $baseName): bool
    {
        $migrationFiles = File::files(database_path('migrations'));

        foreach ($migrationFiles as $file) {
            if (Str::endsWith($file->getFilename(), "_{$baseName}.php") || $file->getFilename() === "{$baseName}.php") {
                return true;
            }
        }

        return false;
    }

    protected function publishFile(string $baseName, string $sourcePath): void
    {
        $timestamp = date('Y_m_d_His');
        $targetName = "{$timestamp}_{$baseName}.php";
        $targetPath = database_path("migrations/{$targetName}");

        while (File::exists($targetPath)) {
            sleep(1);
            $timestamp = date('Y_m_d_His');
            $targetName = "{$timestamp}_{$baseName}.php";
            $targetPath = database_path("migrations/{$targetName}");
        }

        File::copy($sourcePath, $targetPath);
        $this->info("Published: <comment>{$targetName}</comment>");
    }
}
