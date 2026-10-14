<?php

namespace App\Services\SMS;

use App\Enums\PRFSMSStatus;

/**
 * Provider-neutral outcome of sending one SMS.
 */
final readonly class SMSResult
{
    /**
     * @param  array<array-key, mixed>  $raw  the provider's response body, kept for auditing
     */
    public function __construct(
        public ?string $messageId,
        public PRFSMSStatus $status,
        public ?string $cost = null,
        public array $raw = [],
    ) {}
}
