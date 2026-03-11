<?php

namespace Database\Seeders;

use App\Models\ChatBot;
use Illuminate\Database\Seeder;

class ChatBotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bots = [
            [
                'name' => 'Fridah',
                'description' => 'Fridah is an AI-powered chatbot designed to assist users with their inquiries and provide information on various topics.',
            ],
        ];

        foreach ($bots as $bot) {
            ChatBot::updateOrCreate(
                ['name' => $bot['name']],
                ['description' => $bot['description']]
            );
        }
    }
}
