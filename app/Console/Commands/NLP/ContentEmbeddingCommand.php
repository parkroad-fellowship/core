<?php

namespace App\Console\Commands\NLP;

use App\Models\BibleVerse;
use App\Models\MissionFaq;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ContentEmbeddingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prf:content-embedding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command embeds our content database to the NLP service for semantic search and retrieval.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting content embedding process...');

        $documents = collect();

        $this->prepareMissionFaqDocuments($documents);

        $this->prepareKJVDocuments($documents);

        if ($documents->isEmpty()) {
            $this->warn('No documents provided for embedding.');

            return;
        }

        $documents->chunk(100)->each(function ($chunk) {
            $this->info('Processing chunk of '.count($chunk).' documents...');

            $response = Http::withHeaders([
                'x-token' => config('prf.nlp.api_key'),
            ])->post(config('prf.nlp.base_url').'/embedding/init', [
                'texts' => $chunk->values(),
            ]);

            if ($response->successful()) {
                $this->info('Content embedding successful!');
                $this->info('Response: '.$response->body());
            } else {
                $this->error('Content embedding failed.');
                $this->error('Status: '.$response->status());
                $this->error('Error: '.$response->body());
            }
        });

        $this->info('Content embedding process completed.');

    }

    private function prepareMissionFaqDocuments(&$documents)
    {
        MissionFaq::chunkById(100, function ($faqs) use ($documents) {
            foreach ($faqs as $faq) {
                $documents->push(Str::of(Arr::get($faq->toArray(), 'question'))->trim()->prepend('Q: ')
                    ->append(' A: '.Arr::get($faq->toArray(), 'answer'))->__toString());
            }
        });
    }

    private function prepareKJVDocuments(&$documents)
    {
        $translationCode = 'KJV';
        BibleVerse::query()
            ->whereRelation('bibleTranslation', 'code', $translationCode)
            ->with(['bibleBook', 'bibleChapter'])
            ->chunkById(100, function ($verses) use ($documents, $translationCode) {
                foreach ($verses as $verse) {
                    $documents->push(Str::of("({$translationCode}) {$verse->bibleBook->name} {$verse->bibleChapter->chapter_number}:{$verse->verse} - {$verse->text}")->trim()->__toString());
                }
            });

    }
}
