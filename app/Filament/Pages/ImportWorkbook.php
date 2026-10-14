<?php

namespace App\Filament\Pages;

use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFProcessingStatus;
use App\Enums\PRFResponsibleDesk;
use App\Jobs\LedgerImport\ImportWorkbookJob;
use App\Models\LedgerCategory;
use App\Models\LedgerImport;
use App\Services\Finance\WorkbookImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Upload the treasurer's cashbook workbook, review how every row will be booked, map the labels
 * the importer doesn't recognise, then confirm to post everything in the background.
 *
 * Runs on the deployed server: the workbook can't be committed, so this upload is how the
 * fellowship's history and initial chart of accounts get into the app.
 */
class ImportWorkbook extends Page
{
    protected static ?string $title = 'Import Workbook';

    protected static string|\UnitEnum|null $navigationGroup = 'Treasurer';

    protected static ?string $navigationLabel = 'Import Workbook';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.import-workbook';

    private const PREVIEW_TTL_MINUTES = 30;

    public ?string $importULID = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $previewMemo = null;

    private bool $previewMemoized = false;

    public static function canAccess(): bool
    {
        return userCan(LedgerImport::permission('viewAny'));
    }

    public function getSubheading(): ?string
    {
        return 'Bring the treasurer’s cashbook workbook into the app. Nothing is posted until you review the preview and start the import.';
    }

    /**
     * Pick up where the treasurer left off: their latest upload that hasn't been imported yet.
     */
    public function mount(): void
    {
        $this->importULID = LedgerImport::query()
            ->where('status', PRFProcessingStatus::PENDING)
            ->where('imported_by', Auth::id())
            ->latest()
            ->value('ulid');
    }

    /**
     * @return array<int, Action>
     */
    public function getHeaderActions(): array
    {
        return [
            $this->uploadAction(),
            $this->startImportAction(),
            $this->cancelAction(),
        ];
    }

    public function uploadAction(): Action
    {
        return Action::make('upload')
            ->label('Upload workbook')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->visible(fn(): bool => $this->getImport() === null)
            ->modalHeading('Upload the cashbook workbook')
            ->modalDescription(
                'Use the same .xlsx file the treasurer keeps. Only the account sheets are read; the Handover sheet is never opened.',
            )
            ->modalSubmitActionLabel('Upload and preview')
            ->schema([
                FileUpload::make('file')
                    ->label('Workbook (.xlsx)')
                    ->disk(LedgerImport::DISK)
                    ->directory('finance-imports')
                    ->visibility('private')
                    ->storeFileNamesIn('original_name')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->maxSize(20480)
                    ->helperText(
                        'Up to 20 MB. The file is deleted from the server once the import finishes or is cancelled.',
                    )
                    ->required(),
                TextInput::make('year')
                    ->label('Financial year')
                    ->integer()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->default(now()->year)
                    ->helperText('Sheets are matched by year, e.g. “Paybill 2026”.')
                    ->required(),
            ])
            ->action(function (array $data): void {
                $import = LedgerImport::create([
                    'file_path' => $data['file'],
                    'original_name' => $data['original_name'] ?? basename((string) $data['file']),
                    'year' => (int) $data['year'],
                    'status' => PRFProcessingStatus::PENDING,
                    'imported_by' => Auth::id(),
                ]);

                $this->importULID = $import->ulid;
                $this->forgetPreview();

                Notification::make()
                    ->title('Workbook uploaded')
                    ->body(
                        'Review the preview below, map any labels the importer doesn’t recognise, then start the import.',
                    )
                    ->success()
                    ->send();
            });
    }

    public function startImportAction(): Action
    {
        return Action::make('startImport')
            ->label('Start import')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn(): bool => $this->getImport() !== null && !isset($this->getPreviewData()['error']))
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-arrow-down-on-square-stack')
            ->modalHeading('Start the import?')
            ->modalDescription(function (): string {
                $preview = $this->getPreviewData() ?? [];
                $posting = (int) ($preview['mapped_rows'] ?? 0);
                $unmapped = (int) ($preview['unmapped_rows'] ?? 0);

                $text =
                    number_format($posting)
                    . ' rows will be posted to the cashbook in the background. You’ll get a notification when it’s done.';

                if ($unmapped > 0) {
                    $text .=
                        ' '
                        . number_format($unmapped)
                        . ' rows still have unmapped labels and will be left out. You can map them and import the same workbook again later; rows already posted are never duplicated.';
                }

                return $text;
            })
            ->modalSubmitActionLabel('Import now')
            ->action(function (): void {
                $import = $this->getImport();

                if (!$import instanceof LedgerImport) {
                    return;
                }

                // Queued: the job only runs imports in this state, and prune leaves them alone.
                $import->update(['status' => PRFProcessingStatus::PROCESSING, 'error' => null]);
                ImportWorkbookJob::dispatch($import);

                $this->importULID = null;
                $this->forgetPreview();

                Notification::make()
                    ->title('Import started')
                    ->body('You’ll get a notification when it finishes. Progress is shown under Past imports.')
                    ->success()
                    ->send();
            });
    }

    public function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Discard upload')
            ->icon('heroicon-o-trash')
            ->color('gray')
            ->visible(fn(): bool => $this->getImport() !== null)
            ->requiresConfirmation()
            ->modalHeading('Discard this upload?')
            ->modalDescription('The uploaded file is deleted and nothing is posted.')
            ->modalSubmitActionLabel('Discard')
            ->action(function (): void {
                $import = $this->getImport();

                if ($import instanceof LedgerImport) {
                    Storage::disk(LedgerImport::DISK)->delete((string) $import->file_path);
                    $import->update([
                        'status' => PRFProcessingStatus::FAILED,
                        'error' => 'Discarded before importing.',
                        'completed_at' => now(),
                    ]);
                }

                $this->importULID = null;
                $this->forgetPreview();

                Notification::make()->title('Upload discarded')->success()->send();
            });
    }

    /**
     * Rendered on each unmapped label's row: book those rows under an existing category.
     */
    public function mapLabelAction(): Action
    {
        return Action::make('mapLabel')
            ->label('Map')
            ->icon('heroicon-o-link')
            ->size('sm')
            ->modalHeading(fn(array $arguments): string => 'Map “' . $this->labelFor($arguments['label'] ?? '') . '”')
            ->modalDescription('Every row with this label will be booked under the category you choose.')
            ->modalWidth('lg')
            ->schema([
                Select::make('category')
                    ->label('Book under')
                    ->options(fn(): array => $this->categoryOptions())
                    ->searchable()
                    ->native(false)
                    ->required()
                    ->helperText('A receipt on an expense desk is booked as that desk’s refund automatically.'),
            ])
            ->modalSubmitActionLabel('Save mapping')
            ->action(function (array $data, array $arguments): void {
                $this->saveLabelMapping((string) ($arguments['label'] ?? ''), (string) $data['category']);

                Notification::make()->title('Label mapped')->success()->send();
            });
    }

    /**
     * Rendered on each unmapped label's row: create a new category for it on import.
     */
    public function newCategoryAction(): Action
    {
        return Action::make('newCategory')
            ->label('New category')
            ->icon('heroicon-o-plus')
            ->color('gray')
            ->size('sm')
            ->modalHeading(
                fn(array $arguments): string => 'New category for “' . $this->labelFor($arguments['label'] ?? '') . '”',
            )
            ->modalDescription(
                'The category is created when the import runs, and every row with this label is booked under it.',
            )
            ->modalWidth('lg')
            ->fillForm(fn(array $arguments): array => ['name' => $this->labelFor($arguments['label'] ?? '')])
            ->schema([
                TextInput::make('name')->label('Category name')->required()->maxLength(255),
                Select::make('kind')
                    ->label('What is it?')
                    ->options(
                        collect([
                            PRFLedgerCategoryKind::INCOME,
                            PRFLedgerCategoryKind::EXPENSE,
                            PRFLedgerCategoryKind::REFUND,
                            PRFLedgerCategoryKind::CHARGE,
                        ])->mapWithKeys(fn(PRFLedgerCategoryKind $kind): array => [
                            $kind->value => $kind->getLabel(),
                        ])->all(),
                    )
                    ->native(false)
                    ->live()
                    ->required(),
                Select::make('responsible_desk')
                    ->label('Desk')
                    ->options(PRFResponsibleDesk::getOptions())
                    ->native(false)
                    ->placeholder('No desk')
                    ->visible(fn(Get $get): bool => in_array(
                        (int) $get('kind'),
                        [PRFLedgerCategoryKind::EXPENSE->value, PRFLedgerCategoryKind::REFUND->value],
                        true,
                    )),
            ])
            ->modalSubmitActionLabel('Save')
            ->action(function (array $data, array $arguments): void {
                $this->saveLabelMapping((string) ($arguments['label'] ?? ''), [
                    'name' => trim((string) $data['name']),
                    'kind' => (int) $data['kind'],
                    'responsible_desk' => filled($data['responsible_desk'] ?? null)
                        ? (int) $data['responsible_desk']
                        : null,
                ]);

                Notification::make()->title('Category will be created when you import')->success()->send();
            });
    }

    /**
     * Rendered next to each saved mapping: undo it.
     */
    public function removeMappingAction(): Action
    {
        return Action::make('removeMapping')
            ->label('Undo')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->link()
            ->size('sm')
            ->action(function (array $arguments): void {
                $import = $this->getImport();

                if (!$import instanceof LedgerImport) {
                    return;
                }

                $mapping = $import->mapping ?? [];
                unset($mapping[(string) ($arguments['label'] ?? '')]);
                $import->update(['mapping' => $mapping]);
                $this->forgetPreview();
            });
    }

    public function getImport(): ?LedgerImport
    {
        if ($this->importULID === null) {
            return null;
        }

        $import = LedgerImport::query()->where('ulid', $this->importULID)->first();

        if (!$import instanceof LedgerImport || $import->status !== PRFProcessingStatus::PENDING) {
            return null;
        }

        return $import;
    }

    /**
     * Preview of the pending upload with the treasurer's mapping applied. Parsing a workbook is
     * slow, so the result is cached per upload and mapping.
     *
     * @return array<string, mixed>|null
     */
    public function getPreviewData(): ?array
    {
        if ($this->previewMemoized) {
            return $this->previewMemo;
        }

        $this->previewMemoized = true;
        $import = $this->getImport();

        if (!$import instanceof LedgerImport) {
            return $this->previewMemo = null;
        }

        try {
            $preview = Cache::remember(
                $this->previewCacheKey($import),
                now()->addMinutes(self::PREVIEW_TTL_MINUTES),
                function () use ($import): array {
                    $importer = app(WorkbookImporter::class);

                    return $importer
                        ->preview($importer->resolvePath($import), $import->year, $import->mapping ?? [])
                        ->toArray();
                },
            );

            return $this->previewMemo = [...$preview, 'import' => $import];
        } catch (Throwable $exception) {
            return $this->previewMemo = ['error' => $exception->getMessage(), 'import' => $import];
        }
    }

    /**
     * @return array<int, LedgerImport>
     */
    public function getPastImports(): array
    {
        return LedgerImport::query()
            ->where('status', '!=', PRFProcessingStatus::PENDING)
            ->with('importedBy')
            ->latest()
            ->limit(10)
            ->get()
            ->all();
    }

    public function hasImportsInProgress(): bool
    {
        return LedgerImport::query()->where('status', PRFProcessingStatus::PROCESSING)->exists();
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function categoryOptions(): array
    {
        $grouped = [];

        $categories = LedgerCategory::query()
            ->where('is_active', true)
            ->whereNotIn('kind', [PRFLedgerCategoryKind::TRANSFER, PRFLedgerCategoryKind::OPENING_BALANCE])
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        foreach ($categories as $category) {
            $grouped[$category->kind->getLabel()][$category->code ?? 'ulid:' . $category->ulid] = $category->name;
        }

        return $grouped;
    }

    /**
     * @param  string|array{name?: string, kind?: int, responsible_desk?: int|null}  $target
     */
    public function categoryDisplayName(string|array $target): string
    {
        if (is_array($target)) {
            return 'New category: ' . ($target['name'] ?? '');
        }

        $category = str_starts_with($target, 'ulid:')
            ? LedgerCategory::query()->where('ulid', substr($target, 5))->first()
            : LedgerCategory::query()->where('code', $target)->first();

        return $category instanceof LedgerCategory ? $category->name : $target;
    }

    /**
     * The workbook label as the treasurer wrote it, for a normalised mapping key.
     */
    public function labelFor(string $normalised): string
    {
        $unmapped = $this->getPreviewData()['unmapped'] ?? [];

        return (string) ($unmapped[$normalised]['label'] ?? ucwords($normalised));
    }

    /**
     * @param  string|array{name: string, kind: int, responsible_desk: int|null}  $target
     */
    private function saveLabelMapping(string $normalised, string|array $target): void
    {
        $import = $this->getImport();

        if (!$import instanceof LedgerImport || $normalised === '') {
            return;
        }

        $import->update(['mapping' => [...($import->mapping ?? []), $normalised => $target]]);
        $this->forgetPreview();
    }

    private function forgetPreview(): void
    {
        $this->previewMemo = null;
        $this->previewMemoized = false;
    }

    private function previewCacheKey(LedgerImport $import): string
    {
        return 'ledger-import-preview:' . $import->ulid . ':' . md5((string) json_encode($import->mapping ?? []));
    }
}
