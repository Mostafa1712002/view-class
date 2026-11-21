<?php

namespace App\Console\Commands;

use App\Modules\SpecialPrograms\Actions\Import\ImportAlAwwalBank;
use App\Modules\SpecialPrograms\Support\Programs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SpecialProgramsImportAlAwwal extends Command
{
    protected $signature = 'special-programs:import-alawwal
        {--host= : source DB host (default env ALAWWAL_DB_HOST or 127.0.0.1)}
        {--port=3306}
        {--database= : source DB name (default env ALAWWAL_DB_DATABASE)}
        {--username= : default env ALAWWAL_DB_USERNAME}
        {--password= : default env ALAWWAL_DB_PASSWORD}
        {--images= : dir that contains uploads/questions/… (default env ALAWWAL_IMAGES_PATH)}
        {--program=* : qiyas|tahsili|nafis (default all)}
        {--dry-run : read, normalise, dedupe and check images; write nothing}
        {--allow-mass-deactivate : deactivate stale items even when they exceed 20% of a program}
        {--report= : JSON report path (default storage/logs/sp-import-<timestamp>.json)}';

    protected $description = 'Import the «الأول» qiyas / tahsili / nafis question bank (read-only source DB)';

    public function handle(ImportAlAwwalBank $import): int
    {
        $programs = $this->option('program') ?: Programs::keys();
        if ($bad = array_diff($programs, Programs::keys())) {
            $this->error('Unknown program: '.implode(', ', $bad));

            return self::FAILURE;
        }
        $images = $this->option('images') ?: env('ALAWWAL_IMAGES_PATH');
        $database = $this->option('database') ?: env('ALAWWAL_DB_DATABASE');
        if (! $images || ! is_dir($images) || ! $database) {
            $this->error('Need --database and an existing --images directory (or ALAWWAL_DB_DATABASE / ALAWWAL_IMAGES_PATH).');

            return self::FAILURE;
        }

        config()->set('database.connections.alawwal', [
            'driver' => 'mysql',
            'host' => $this->option('host') ?: env('ALAWWAL_DB_HOST', '127.0.0.1'),
            'port' => $this->option('port'),
            'database' => $database,
            'username' => $this->option('username') ?: env('ALAWWAL_DB_USERNAME'),
            'password' => $this->option('password') ?? env('ALAWWAL_DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ]);

        $dry = (bool) $this->option('dry-run');
        $this->info(($dry ? 'DRY RUN' : 'IMPORT').' → '.config('database.connections.'.config('database.default').'.database').' from '.$database);

        $report = $import->execute(DB::connection('alawwal'), $images, $programs, $dry, fn ($m) => $this->line($m), (bool) $this->option('allow-mass-deactivate'));

        $rows = [];
        foreach ($report as $program => $r) {
            $rows[] = [
                $program, $r['rows_read'], $r['items_before_dedupe'], $r['dedupe_groups'].'/'.$r['dedupe_rows_merged'],
                $r['items_after_dedupe'], $r['passages_after_dedupe'].'/'.$r['sub_questions'],
                "{$r['items']['created']}/{$r['items']['updated']}/{$r['items']['unchanged']}",
                "{$r['images']['copied']}/{$r['images']['unchanged']}/{$r['images_missing']}",
                $r['orphan_links'], $r['decode_failures'] + $r['unknown_skill'] + $r['image_conflicts'],
                $r['deactivate_blocked'] ? "refused ({$r['deactivate_blocked']})" : $r['deactivated'],
            ];
        }
        $this->table(['program', 'rows', 'items pre', 'dedupe grp/rows', 'items post', 'passages/subq', 'new/upd/same', 'img copy/same/miss', 'orphan links', 'errors', 'deactivated'], $rows);
        foreach ($report as $program => $r) {
            if ($r['deactivate_blocked']) {
                $this->warn("{$program}: {$r['deactivate_blocked']} items no longer in the source is more than ".(ImportAlAwwalBank::MASS_DEACTIVATE_RATIO * 100).'% of the program — nothing deactivated. Check the source DB, then re-run with --allow-mass-deactivate.');
            }
        }

        $path = $this->option('report') ?: storage_path('logs/sp-import-'.now()->format('Ymd-His').($dry ? '-dry' : '').'.json');
        file_put_contents($path, json_encode(['dry_run' => $dry, 'programs' => $report], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->info("Report: {$path}");

        return collect($report)->sum(fn ($r) => $r['decode_failures'] + $r['unknown_skill'] + $r['image_conflicts'] + $r['deactivate_blocked']) ? self::FAILURE : self::SUCCESS;
    }
}
