<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AddPlayer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-player';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //         curl -X "POST" "https://api.onesignal.com/apps/<APP_ID>/users" \
        //      -H 'Content-Type: application/json; charset=utf-8' \
        //      -d $'{
        //   "properties": {
        //     "country": "US",
        //     "tags": {
        //       "favorite_team": "Lakers"
        //     },
        //     "language": "EN"
        //   },
        //   "identity": {
        //     "external_id": "test"
        //   }
        // }'

        $user = User::find(1);
        $appId = config('services.onesignal.app_id');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json; charset=utf-8',
            'Authorization' => 'Basic',
        ])->post("https://api.onesignal.com/apps/$appId/users", [
            'subscriptions' => [
                [
                    'type' => 'Email',
                    'token' => $user->email,
                    'enabled' => true,

                ],
            ],
            'properties' => [
                'country' => 'KE',
                'language' => 'EN',
                'tags' => [
                    'first_name' => $user->name,
                    'last_name' => $user->name,
                ],
            ],
            'identity' => [
                'external_id' => $user->ulid,
            ],
        ]);

        // {
        //     "identity": {
        //       "onesignal_id": "567491ee-9105-4a87-9cbc-ed78a571645b",
        //     },
        //     "properties": {
        //       "tags": {
        //         "first_name": "John",
        //         "last_name": "Smith"
        //       }
        //     }
        //   }

        $results = $response->json();

        if (Arr::has($results, 'errors')) {
            Log::error('Failed to add player to OneSignal', $results);

            return;
        }

        User::query()
            ->where('id', $user->id)
            ->update([
                'one_signal_player_id' => $results['identity']['onesignal_id'],
            ]);
    }
}
