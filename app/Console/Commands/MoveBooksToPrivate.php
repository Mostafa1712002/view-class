<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Moves uploaded book PDFs from the public disk (served as /storage/books/...)
 * to the private `local` disk, keeping the same relative path, so the file is
 * only reachable through the permission-checked book routes.
 *
 * Idempotent: a book whose file is already on the private disk is skipped.
 * The public copy is deleted only after the private copy matches it by size
 * and checksum. Covers (books/covers/) stay public and are not touched.
 */
class MoveBooksToPrivate extends Command
{
    protected $signature = 'books:move-to-private {--dry-run : Report what would move without touching any file}';

    protected $description = 'Move book PDFs from the public disk to the private disk';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $counts = ['moved' => 0, 'already_private' => 0, 'missing' => 0, 'failed' => 0];
        $referenced = [];

        Book::withTrashed()
            ->where('source', Book::SOURCE_FILE)
            ->whereNotNull('file_path')
            ->orderBy('id')
            ->each(function (Book $book) use ($dry, $public, $private, &$counts, &$referenced) {
                $path = $book->file_path;
                $referenced[$path] = true;

                if (! $public->exists($path)) {
                    if ($private->exists($path)) {
                        $counts['already_private']++;
                    } else {
                        $counts['missing']++;
                        $this->warn("Book #{$book->id}: file not found on either disk ({$path})");
                    }

                    return;
                }

                if ($dry) {
                    $counts['moved']++;
                    $this->line("Would move book #{$book->id}: {$path}");

                    return;
                }

                if (! $this->sameFile($public, $private, $path)) {
                    $stream = $public->readStream($path);
                    $private->writeStream($path, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                if (! $this->sameFile($public, $private, $path)) {
                    $counts['failed']++;
                    $this->error("Book #{$book->id}: private copy does not match, public file kept ({$path})");

                    return;
                }

                $public->delete($path);
                $counts['moved']++;
            });

        $orphans = collect($public->files('books'))->reject(fn ($f) => isset($referenced[$f]))->count();

        $summary = sprintf(
            '%s moved=%d already_private=%d missing=%d failed=%d unreferenced_public_pdfs=%d',
            $dry ? '[dry-run]' : '',
            $counts['moved'], $counts['already_private'], $counts['missing'], $counts['failed'], $orphans,
        );
        $this->info(trim($summary));
        if (! $dry) {
            Log::info('books:move-to-private '.trim($summary));
        }

        return $counts['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function sameFile($public, $private, string $path): bool
    {
        return $private->exists($path)
            && $private->size($path) === $public->size($path)
            && $private->checksum($path) === $public->checksum($path);
    }
}
