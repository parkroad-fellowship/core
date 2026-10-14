<?php

use App\Enums\PRFDeliveryStatus;
use App\Enums\PRFFinancialAccountType;
use App\Enums\PRFReceiptChannel;
use App\Enums\PRFSMSStatus;
use App\Jobs\LedgerEntry\CreateJob as CreateLedgerEntryJob;
use App\Jobs\ReceiptDelivery\CreateJob as CreateReceiptDeliveryJob;
use App\Models\LedgerEntry;
use App\Models\ReceiptDelivery;
use App\Models\SMSLog;
use App\Notifications\LedgerEntry\LedgerEntryReceiptedNotification;
use App\Services\Finance\ChartOfAccounts;
use App\Services\Finance\ReceiptDocument;
use App\Services\SMS\SMSGateway;
use App\Services\SMS\SMSManager;
use App\Services\SMS\SMSResult;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    fakePDFRendering();
});

function receiptedGift(array $overrides = []): LedgerEntry
{
    $chart = app(ChartOfAccounts::class);

    return CreateLedgerEntryJob::dispatchSync([
        'financial_account_ulid' => $chart->account(PRFFinancialAccountType::CASH)->ulid,
        'ledger_category_ulid' => $chart->category('income.mission_contribution')->ulid,
        'amount' => 10_000,
        'transacted_on' => '2026-01-23',
        'counterparty' => 'Martin Kiarie',
        ...$overrides,
    ]);
}

function useFakeSMSProvider(): void
{
    configureIntegration(['sms.driver' => 'fake-provider']);

    app(SMSManager::class)->extend('fake-provider', fn() => new class extends SMSGateway {
        public function name(): string
        {
            return 'fake-provider';
        }

        protected function deliver(string $recipient, string $message): SMSResult
        {
            return new SMSResult('fake-1', PRFSMSStatus::SENT);
        }
    });
}

it('emails the giver a thank-you with the PDF receipt when asked to', function () {
    Notification::fake();

    $entry = receiptedGift(['giver_email' => 'martin@example.com', 'send_receipt' => true]);

    Notification::assertSentTo(new AnonymousNotifiable(), LedgerEntryReceiptedNotification::class, function (
        LedgerEntryReceiptedNotification $notification,
        array $channels,
        AnonymousNotifiable $notifiable,
    ) use ($entry) {
        $mail = $notification->toMail($notifiable);

        return (
            $notifiable->routes['mail'] === 'martin@example.com'
            && $notification->ledgerEntry->is($entry)
            && $mail->rawAttachments[0]['name'] === "Receipt {$entry->receipt_number}.pdf"
        );
    });

    expect(ReceiptDelivery::query()->sole())
        ->status->toBe(PRFDeliveryStatus::SENT)
        ->channel->toBe(PRFReceiptChannel::EMAIL);
});

it('sends nothing unless the treasurer asks for a receipt', function () {
    Notification::fake();

    receiptedGift(['giver_email' => 'martin@example.com']);

    Notification::assertNothingSent();
    expect(ReceiptDelivery::query()->count())->toBe(0);
});

it('texts the giver a thank-you with a link to their receipt', function () {
    useFakeSMSProvider();

    $entry = receiptedGift(['giver_phone' => '0712345678', 'send_receipt' => true]);

    $log = SMSLog::query()->sole();

    expect($log->smsLoggable->is($entry))
        ->toBeTrue()
        ->and($log->message)
        ->toContain($entry->receipt_number)
        ->toContain('/receipts/')
        ->and(ReceiptDelivery::query()->sole()->status)
        ->toBe(PRFDeliveryStatus::SENT);
});

it('records a failed SMS receipt when the fellowship has no SMS provider', function () {
    receiptedGift(['giver_phone' => '0712345678', 'send_receipt' => true]);

    expect(ReceiptDelivery::query()->sole())->status->toBe(PRFDeliveryStatus::FAILED)->error->not->toBeEmpty();
});

it('prepares a WhatsApp message the treasurer sends from their phone', function () {
    $entry = receiptedGift(['giver_phone' => '0712 345 678']);

    $delivery = CreateReceiptDeliveryJob::dispatchSync([
        'ledger_entry_ulid' => $entry->ulid,
        'channel' => PRFReceiptChannel::WHATSAPP->value,
    ]);

    expect($delivery->status)
        ->toBe(PRFDeliveryStatus::LINK_READY)
        ->and($delivery->share_url)
        ->toStartWith('https://wa.me/254712345678?text=')
        ->and(rawurldecode($delivery->share_url))
        ->toContain($entry->receipt_number)
        ->toContain('Your giving at work');
});

it('refuses to send a receipt for anything but income', function () {
    $chart = app(ChartOfAccounts::class);
    $expense = CreateLedgerEntryJob::dispatchSync([
        'financial_account_ulid' => $chart->account(PRFFinancialAccountType::CASH)->ulid,
        'ledger_category_ulid' => $chart->category('expense.other')->ulid,
        'amount' => 100,
    ]);

    CreateReceiptDeliveryJob::dispatchSync([
        'ledger_entry_ulid' => $expense->ulid,
        'channel' => PRFReceiptChannel::EMAIL->value,
        'recipient' => 'someone@example.com',
    ]);
})->throws(InvalidArgumentException::class);

it('lets the giver open their receipt from a signed link only', function () {
    $entry = receiptedGift();
    $url = app(ReceiptDocument::class)->url($entry);

    $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->get(route('receipts.show', ['tenant' => $entry->tenant_id, 'ulid' => $entry->ulid]))->assertForbidden();
});
