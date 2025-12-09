<?php

namespace Database\Seeders;

use App\Helpers\Utils;
use App\Models\BibleBook;
use App\Models\BibleChapter;
use App\Models\BibleTranslation;
use App\Models\BibleVerse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BibleVerseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Bible verses ...');

        $this->seedKJV();

    }

    private function seedKJV(): void
    {
        $jsonPath = database_path('seeders/data/KJV.json');

        if (! File::exists($jsonPath)) {
            $this->command->error("File not found: {$jsonPath}");

            return;
        }

        try {
            // Disable query log to save memory
            DB::connection()->disableQueryLog();

            // Load JSON file
            ini_set('memory_limit', '512M');
            $bible = Storage::disk('seed')->json('KJV.json');

            $translationName = $bible['translation'];
            $translationCode = 'KJV';

            // Truncate data before re-inserting
            BibleVerse::query()
                ->where(
                    'translation_id',
                    BibleTranslation::query()
                        ->where('code', $translationCode)
                        ->limit(1)
                        ->select('id')
                )->truncate();

            BibleChapter::query()
                ->where(
                    'bible_translation_id',
                    BibleTranslation::query()
                        ->where('code', $translationCode)
                        ->limit(1)
                        ->select('id')
                )->truncate();

            BibleBook::query()
                ->where(
                    'bible_translation_id',
                    BibleTranslation::query()
                        ->where('code', $translationCode)
                        ->limit(1)
                        ->select('id')
                )->truncate();

            // Finish truncation

            $translation = DB::table('bible_translations')
                ->where('code', $translationCode)
                ->first();

            if (! $translation) {
                $translationId = DB::table('bible_translations')->insertGetId([
                    'ulid' => Utils::generateUlid(),
                    'name' => $translationName,
                    'code' => $translationCode,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $translationId = $translation->id;
            }

            $this->command->info("Using translation: {$translationName}");

            $totalBooks = count($bible['books'] ?? []);
            $this->command->info("Total books to seed: {$totalBooks}");

            $progressBar = $this->command->getOutput()->createProgressBar($totalBooks);
            $progressBar->start();

            foreach ($bible['books'] as $bookIndex => $bookData) {
                $bookName = $bookData['name'];

                // Create or get book
                $book = DB::table('bible_books')
                    ->where('bible_translation_id', $translationId)
                    ->where('name', $bookName)
                    ->first();

                if (! $book) {
                    $bookId = DB::table('bible_books')->insertGetId([
                        'ulid' => Utils::generateUlid(),
                        'bible_translation_id' => $translationId,
                        'name' => $bookName,
                        'order' => $bookIndex + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $bookId = $book->id;
                }

                // Process chapters
                foreach ($bookData['chapters'] as $chapterData) {
                    $chapterNumber = $chapterData['chapter'];

                    // Create or get chapter
                    $chapter = DB::table('bible_chapters')
                        ->where('bible_translation_id', $translationId)
                        ->where('bible_book_id', $bookId)
                        ->where('chapter_number', $chapterNumber)
                        ->first();

                    if (! $chapter) {
                        $chapterId = DB::table('bible_chapters')->insertGetId([
                            'ulid' => Utils::generateUlid(),
                            'bible_translation_id' => $translationId,
                            'bible_book_id' => $bookId,
                            'chapter_number' => $chapterNumber,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $chapterId = $chapter->id;
                    }

                    // Batch insert verses without checking for duplicates
                    $versesData = [];
                    foreach ($chapterData['verses'] as $verseData) {
                        $versesData[] = [
                            'ulid' => Utils::generateUlid(),
                            'bible_translation_id' => $translationId,
                            'bible_book_id' => $bookId,
                            'bible_chapter_id' => $chapterId,
                            'verse_number' => $verseData['verse'],
                            'text' => $verseData['text'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    // Insert verses in batches
                    if (! empty($versesData)) {
                        $batchSize = 1000;
                        foreach (array_chunk($versesData, $batchSize) as $versesBatch) {
                            DB::table('bible_verses')->insert($versesBatch);
                        }
                    }

                    unset($versesData);
                }

                $progressBar->advance();

                // Free memory periodically
                unset($bookData);
            }

            $progressBar->finish();
            $this->command->newLine();
            $this->command->info('Bible verses seeded successfully!');

            // Clean up large data structure
            unset($bible);

        } catch (\Exception $e) {
            $this->command->error('Error seeding verses: '.$e->getMessage());
        }
    }
}
