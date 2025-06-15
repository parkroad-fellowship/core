<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\V2\AttachMediaRequest;
use App\Jobs\Media\DeleteTemporaryFileJob;
use App\Models\Expense;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function attachMedia(AttachMediaRequest $request, string $expenseUlid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $expense = Expense::query()
            ->where('ulid', $expenseUlid)
            ->firstOrFail();

        $url = Storage::disk('azure_tmp')->url($validated['media_file_storage_path']);

        $media = $expense
            ->addMediaFromUrl($url)
            ->toMediaCollection(
                Arr::first(
                    Expense::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );

        // Delete from the temp disk and the main disk temp location
        DeleteTemporaryFileJob::dispatch(
            ['azure_tmp', 'azure'],
            $validated['media_file_storage_path'],
        );

        return new \App\Http\Resources\Media\Resource($media);
    }
}
