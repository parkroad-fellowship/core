<?php

namespace App\Services\SMS;

use App\Contracts\Services\SMSGatewayInterface;
use App\Enums\PRFSMSStatus;
use App\Helpers\Utils;
use App\Models\SMSLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Behaviour shared by every SMS provider: phone normalisation, the non-production
 * test-number redirect and sms_logs bookkeeping. A provider only implements deliver().
 */
abstract class SMSGateway implements SMSGatewayInterface
{
    /**
     * Driver name stored on sms_logs.provider, e.g. "advanta".
     */
    abstract public function name(): string;

    /**
     * Hand the message to the provider. $recipient is already E.164 formatted.
     */
    abstract protected function deliver(string $recipient, string $message): SMSResult;

    public function deliveryStatus(string $messageId): PRFSMSStatus
    {
        return PRFSMSStatus::UNKNOWN;
    }

    public function send(string $phoneNumber, string $message, ?Model $smsLoggable = null): SMSResult
    {
        $recipient = $this->normalise($phoneNumber);

        $smsLog = SMSLog::create([
            'provider' => $this->name(),
            'status' => PRFSMSStatus::QUEUED,
            'phone' => $recipient,
            'message' => $message,
            'sms_loggable_id' => $smsLoggable?->getKey(),
            'sms_loggable_type' => $smsLoggable?->getMorphClass(),
        ]);

        try {
            $result = $this->deliver($this->destination($recipient), $message);
        } catch (Throwable $exception) {
            $smsLog->update([
                'status' => PRFSMSStatus::FAILED,
                'response' => ['error' => $exception->getMessage()],
            ]);

            throw $exception;
        }

        $smsLog->update([
            'message_id' => $result->messageId,
            'status' => $result->status,
            'cost' => $result->cost,
            'response' => $result->raw,
        ]);

        return $result;
    }

    protected function http(): PendingRequest
    {
        return Http::acceptJson()->timeout((int) config('prf.sms.timeout', 15));
    }

    protected function normalise(string $phoneNumber): string
    {
        return (
            Utils::toE164($phoneNumber) ?? throw new InvalidArgumentException("Invalid phone number: {$phoneNumber}")
        );
    }

    /**
     * Outside production every message goes to the configured test number instead.
     */
    private function destination(string $recipient): string
    {
        if (app()->environment('production')) {
            return $recipient;
        }

        $testNumber = config('prf.sms.test_phone_number');

        if (!is_string($testNumber) || $testNumber === '') {
            throw new RuntimeException('Set SMS_TEST_PHONE_NUMBER to send SMS outside production.');
        }

        return $this->normalise($testNumber);
    }
}
