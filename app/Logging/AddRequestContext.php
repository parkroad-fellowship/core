<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Context;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class AddRequestContext
{
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(new class implements ProcessorInterface
        {
            public function __invoke(LogRecord $record): LogRecord
            {
                $requestId = Context::get('request_id');

                if ($requestId) {
                    return $record->with(extra: array_merge($record->extra, [
                        'request_id' => $requestId,
                    ]));
                }

                return $record;
            }
        });
    }
}
