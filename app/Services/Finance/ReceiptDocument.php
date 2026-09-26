<?php

namespace App\Services\Finance;

use App\Helpers\Utils;
use App\Models\LedgerEntry;
use App\Settings\TenantSettings;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Number;
use Throwable;

use function Spatie\LaravelPdf\Support\pdf;

/**
 * Everything a giver receives for receipted income: the PDF, the message text and the links.
 */
class ReceiptDocument
{
    public function __construct(
        private readonly ImpactSummaryService $impact,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function data(LedgerEntry $entry): array
    {
        $entry->loadMissing(['financialAccount', 'ledgerCategory', 'member']);

        return [
            'entry' => $entry,
            'organisation' => $this->organisation(),
            'giver' => $this->giverName($entry),
            'amountInWords' => $this->amountInWords($entry->amount),
            'impact' => $this->impact->yearToDate($entry->transacted_on),
        ];
    }

    public function pdf(LedgerEntry $entry): string
    {
        return pdf()
            ->view('prf.finance.receipt-pdf', $this->data($entry))
            ->name($this->filename($entry))
            ->generatePdfContent();
    }

    public function filename(LedgerEntry $entry): string
    {
        return "Receipt {$entry->receipt_number}.pdf";
    }

    /**
     * A permanent link the giver can open without signing in.
     */
    public function url(LedgerEntry $entry): string
    {
        return URL::signedRoute('receipts.show', ['tenant' => $entry->tenant_id, 'ulid' => $entry->ulid]);
    }

    public function acknowledgement(LedgerEntry $entry): string
    {
        return sprintf(
            'Dear %s, %s gratefully acknowledges your gift of KES %s towards %s, received on %s. Receipt No. %s. Thank you for partnering with us.',
            $this->giverName($entry),
            $this->organisation(),
            number_format($entry->amount),
            $entry->ledgerCategory?->name ?? 'the fellowship',
            $entry->transacted_on->format('j M Y'),
            $entry->receipt_number,
        );
    }

    public function smsText(LedgerEntry $entry): string
    {
        return $this->acknowledgement($entry) . ' ' . $this->url($entry);
    }

    public function whatsAppText(LedgerEntry $entry): string
    {
        $impact = $this->impact->yearToDate($entry->transacted_on);

        return implode("\n\n", [
            $this->acknowledgement($entry),
            'Your giving at work — ' . $impact->headline(),
            'Download your receipt: ' . $this->url($entry),
        ]);
    }

    /**
     * Opens WhatsApp with the message ready for the treasurer to send from their own phone.
     */
    public function whatsAppURL(LedgerEntry $entry, ?string $phone = null): string
    {
        $phone = Utils::toE164($phone ?? $entry->giver_phone);
        $number = $phone === null ? '' : ltrim($phone, '+');

        return "https://wa.me/{$number}?text=" . rawurlencode($this->whatsAppText($entry));
    }

    private function organisation(): string
    {
        return TenantSettings::fromCurrentTenant()->organizationName;
    }

    private function giverName(LedgerEntry $entry): string
    {
        return $entry->counterparty ?: $entry->member?->full_name ?: 'Friend';
    }

    private function amountInWords(int $amount): string
    {
        try {
            return ucfirst((string) Number::spell($amount)) . ' shillings only';
        } catch (Throwable) {
            return number_format($amount) . ' shillings only';
        }
    }
}
