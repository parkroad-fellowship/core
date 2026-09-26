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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Upload the treasurer's cashbook workbook, preview how each row will be booked, map the
 * labels the parser doesn't recognise, then confirm to post everything in the background.
 */
class ImportWorkbook extends Page
{
    protected static ?string $title = 'Import Workbook';

    protected static string|\UnitEnum|null $navigationGroup = 'Treasurer';

    protected static ?string $navigationLabel = 'Import Workbook';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.import-workbook';

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

    /**
     * @return array<int, Action>
     */
    public function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Upload workbook')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->visible(fn(): bool => $this->getImport() === null)
                ->schema([
                    FileUpload::make('file')
                        ->label('Workbook (.xlsx)')
                        ->disk('local')
                        ->directory('finance-imports')
                        ->visibility('private')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->maxSize(20480)
                        ->helperText('The treasurer’s cashbook workbook. The Handover sheet is never read. Max 20MB.')
                        ->required(),
                    TextInput::make('year')
                        ->numeric()
                        ->minValue(2000)
                        ->maxValue(2100)
                        ->default(now()->year)
                        ->required(),
                ])
                ->action(fn(array $data): mixed => $this->storeUpload($data)),

            Action::make('mapLabel')
                ->label('Map label')
                ->icon('heroicon-o-link')
                ->color('gray')
                ->visible(fn(): bool => $this->getImport() !== null && $this->unmappedOptions() !== [])
                ->schema([
                    Select::make('label')
                        ->label('Workbook label')
                        ->options(fn(): array => $this->unmappedOptions())
                        ->searchable()
                        ->required(),
                    Select::make('category')
                        ->label('Map to category')
                        ->options(fn(): array => $this->categoryOptions())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->saveLabelMapping($data['label'], $data['category']);

                    Notification::make()->title('Label mapped')->success()->send();
                }),

            Action::make('createCategory')
                ->label('New category')
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->visible(fn(): bool => $this->getImport() !== null && $this->unmappedOptions() !== [])
                ->modalHeading('Create a category for a label')
                ->schema([
                    Select::make('label')
                        ->label('Workbook label')
                        ->options(fn(): array => $this->unmappedOptions())
                        ->searchable()
                        ->required(),
                    TextInput::make('name')->required()->maxLength(255),
                    Select::make('kind')->options(PRFLedgerCategoryKind::class)->required(),
                    Select::make('responsible_desk')->options(PRFResponsibleDesk::getOptions())->placeholder('No desk'),
                ])
                ->action(function (array $data): void {
                    $this->saveLabelMapping($data['label'], [
                        'name' => $data['name'],
                        'kind' => (int) $data['kind'],
                        'responsible_desk' => $data['responsible_desk'] !== null
                            ? (int) $data['responsible_desk']
                            : null,
                    ]);

                    Notification::make()->title('Category will be created on import')->success()->send();
                }),

            Action::make('confirm')
                ->label('Start import')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Start the import?')
                ->modalDescription(
                    'Every mapped row will be posted to the cashbook in the background. Re-importing the same workbook later only adds new rows.',
                )
                ->visible(fn(): bool => $this->getImport() !== null)
                ->action(function (): void {
                    $import = $this->getImport();

                    if (!$import instanceof LedgerImport) {
                        return;
                    }

                    ImportWorkbookJob::dispatch($import);

                    Notification::make()->title('Importing… you’ll get an email when it finishes')->success()->send();

                    $this->importULID = null;
                }),

            Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(): bool => $this->getImport() !== null)
                ->action(function (): void {
                    $import = $this->getImport();

                    if ($import instanceof LedgerImport) {
                        Storage::disk(LedgerImport::DISK)->delete((string) $import->file_path);
                        $import->update([
                            'status' => PRFProcessingStatus::FAILED,
                            'error' => 'Cancelled by the treasurer.',
                            'completed_at' => now(),
                        ]);
                    }

                    $this->importULID = null;

                    Notification::make()->title('Upload cancelled')->success()->send();
                }),
        ];
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
     * Preview of the pending upload, with the treasurer's saved mapping applied.
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
            $path = app(WorkbookImporter::class)->resolvePath($import);
            $preview = app(WorkbookImporter::class)->preview($path, $import->year, $import->mapping ?? []);

            return $this->previewMemo = [...$preview->toArray(), 'import' => $import];
        } catch (Throwable $exception) {
            return $this->previewMemo = ['error' => $exception->getMessage(), 'import' => $import];
        }
    }

    /**
     * @return array<int, LedgerImport>
     */
    public function getPastImports(): array
    {
        return LedgerImport::query()->latest()->limit(10)->get()->all();
    }

    /**
     * @return array<string, string>
     */
    public function unmappedOptions(): array
    {
        $data = $this->getPreviewData();

        if (!is_array($data) || !isset($data['unmapped']) || !is_array($data['unmapped'])) {
            return [];
        }

        $options = [];

        foreach ($data['unmapped'] as $normalised => $entry) {
            $options[$normalised] = $entry['label'] . ' (' . $entry['count'] . ' rows)';
        }

        return $options;
    }

    /**
     * @return array<string, array<string, string>|string>
     */
    public function categoryOptions(): array
    {
        $grouped = [];

        foreach (LedgerCategory::query()->where('is_active', true)->orderBy('name')->get() as $category) {
            $grouped[$category->kind->getLabel()][$category->code ?? 'ulid:' . $category->ulid] = $category->name;
        }

        return $grouped;
    }

    /**
     * @param  string|array{name: string, kind: int, responsible_desk: int|null}  $target
     */
    public function categoryDisplayName(string|array $target): string
    {
        if (is_array($target)) {
            return 'New: ' . ($target['name'] ?? 'category');
        }

        $category = str_starts_with($target, 'ulid:')
            ? LedgerCategory::query()->where('ulid', substr($target, 5))->first()
            : LedgerCategory::query()->where('code', $target)->first();

        return $category instanceof LedgerCategory ? $category->name : $target;
    }

    /**
     * @param  array{file: string, year: string|int}  $data
     */
    private function storeUpload(array $data): mixed
    {
        $import = LedgerImport::create([
            'file_path' => $data['file'],
            'original_name' => basename($data['file']),
            'year' => (int) $data['year'],
            'status' => PRFProcessingStatus::PENDING,
            'imported_by' => Auth::id(),
        ]);

        $this->importULID = $import->ulid;

        Notification::make()->title('Workbook uploaded — check the preview below')->success()->send();

        return null;
    }

    /**
     * @param  string|array{name: string, kind: int, responsible_desk: int|null}  $target
     */
    private function saveLabelMapping(string $normalised, string|array $target): void
    {
        $import = $this->getImport();

        if (!$import instanceof LedgerImport) {
            return;
        }

        $import->update(['mapping' => [...($import->mapping ?? []), $normalised => $target]]);
    }
}
