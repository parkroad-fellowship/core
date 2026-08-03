<?php

use App\Contracts\Services\NlpServiceInterface;
use App\Contracts\Services\PaymentGatewayInterface;
use App\Contracts\Services\SMSGatewayInterface as SmsGatewayInterface;
use App\Jobs\NLP\EmbedContentJob;
use App\Jobs\PayStack\InitialiseTransactionJob;
use App\Jobs\SMS\SendSMSJob;
use Illuminate\Database\Eloquent\Model;

it('SendSMSJob returns early in non-production environment', function () {
    $mockGateway = new class implements SmsGatewayInterface
    {
        public static array $sent = [];

        public function send(string $phoneNumber, string $message, ?Model $smsLoggable = null): array
        {
            self::$sent[] = ['phone' => $phoneNumber, 'message' => $message];

            return ['message_id' => 'mock-123', 'response' => []];
        }

        public function checkBlacklist(string $messageId): bool
        {
            return false;
        }
    };

    $job = new SendSMSJob('+254712345678', 'Test message');
    $job->handle($mockGateway);

    expect($mockGateway::$sent)->toHaveCount(0);
});

it('InitialiseTransactionJob resolves PaymentGatewayInterface via constructor injection', function () {
    $mockGateway = new class implements PaymentGatewayInterface
    {
        public function initializeTransaction(array $data): array
        {
            return ['status' => true, 'data' => ['authorization_url' => 'https://pay.example.com']];
        }

        public function verifyTransaction(string $reference): array
        {
            return ['status' => true, 'data' => []];
        }

        public function verifyWebhook(\Illuminate\Http\Request $request): bool
        {
            return true;
        }
    };

    $job = new InitialiseTransactionJob([
        'email' => 'test@example.com',
        'amount' => 5000,
        'id' => 'ref-123',
    ]);

    $result = $job->handle($mockGateway);

    expect($result['authorization_url'])->toBe('https://pay.example.com');
});

it('EmbedContentJob resolves NlpServiceInterface via constructor injection', function () {
    $mockNlp = new class implements NlpServiceInterface
    {
        public static array $embedded = [];

        public function embedContent(array $documents): void
        {
            self::$embedded = $documents;
        }

        public function enquire(string $question, array $conversationHistory): array
        {
            return ['answer' => '', 'meta' => []];
        }
    };

    $job = new EmbedContentJob(['doc1', 'doc2']);
    $job->handle($mockNlp);

    expect($mockNlp::$embedded)->toBe(['doc1', 'doc2']);
});

it('can swap SMS provider at runtime', function () {
    $customGateway = new class implements SmsGatewayInterface
    {
        public function send(string $phoneNumber, string $message, ?Model $smsLoggable = null): array
        {
            return ['message_id' => 'custom-twilio-123', 'response' => ['provider' => 'twilio']];
        }

        public function checkBlacklist(string $messageId): bool
        {
            return false;
        }
    };

    app()->bind(SmsGatewayInterface::class, fn () => $customGateway);

    $resolved = app(SmsGatewayInterface::class);
    $result = $resolved->send('+1234567890', 'test');

    expect($result['message_id'])->toBe('custom-twilio-123')
        ->and($result['response']['provider'])->toBe('twilio');
});

it('can swap payment provider at runtime', function () {
    $customGateway = new class implements PaymentGatewayInterface
    {
        public function initializeTransaction(array $data): array
        {
            return ['status' => true, 'data' => ['checkout_url' => 'https://stripe.example.com/pay']];
        }

        public function verifyTransaction(string $reference): array
        {
            return ['status' => true, 'data' => ['status' => 'succeeded']];
        }

        public function verifyWebhook(\Illuminate\Http\Request $request): bool
        {
            return true;
        }
    };

    app()->bind(PaymentGatewayInterface::class, fn () => $customGateway);

    $resolved = app(PaymentGatewayInterface::class);
    $result = $resolved->initializeTransaction([
        'email' => 'test@test.com',
        'amount' => 1000,
        'id' => 'ref-1',
    ]);

    expect($result['data']['checkout_url'])->toBe('https://stripe.example.com/pay');
});
