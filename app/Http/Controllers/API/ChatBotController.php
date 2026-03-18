<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatBot\CreateRequest;
use App\Http\Requests\ChatBot\UpdateRequest;
use App\Http\Resources\ChatBot\Resource;
use App\Jobs\ChatBot\CreateJob;
use App\Jobs\ChatBot\UpdateJob;
use App\Models\ChatBot;
use Spatie\QueryBuilder\QueryBuilder;

class ChatBotController extends Controller
{
    protected ?string $modelClass = ChatBot::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(ChatBot::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...ChatBot::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = ChatBot::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(ChatBot::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...ChatBot::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
