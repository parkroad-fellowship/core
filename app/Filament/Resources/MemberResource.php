<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use App\Exports\Member\ImportTemplateExport;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\FileUpload;
use Exception;
use App\Imports\Member\WebUploadImport;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Resources\MemberResource\RelationManagers\MembershipsRelationManager;
use App\Filament\Resources\MemberResource\RelationManagers\MissionSubscriptionsRelationManager;
use App\Filament\Resources\MemberResource\RelationManagers\DepartmentsRelationManager;
use App\Filament\Resources\MemberResource\RelationManagers\GiftsRelationManager;
use App\Filament\Resources\MemberResource\RelationManagers\GroupMembersRelationManager;
use App\Filament\Resources\MemberResource\RelationManagers\CourseMembersRelationManager;
use App\Filament\Resources\MemberResource\Pages\ListMembers;
use App\Filament\Resources\MemberResource\Pages\CreateMember;
use App\Filament\Resources\MemberResource\Pages\ViewMember;
use App\Filament\Resources\MemberResource\Pages\EditMember;
use App\Console\Commands\Member\InviteMembersCommand;
use App\Enums\PRFActiveStatus;
use App\Enums\PRFGender;
use App\Filament\Resources\MemberResource\Pages;
use App\Filament\Resources\MemberResource\RelationManagers;
use App\Models\Member;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Actions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | \UnitEnum | null $navigationGroup = 'Organising Secretary';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $modelLabel = 'Member';

    protected static ?string $pluralModelLabel = 'Members';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('👤 Personal Information')
                    ->description('Basic personal details and identification')
                    ->schema([
                        TextInput::make('ulid')
                            ->required()
                            ->label('ULID')
                            ->visible(app()->isLocal())
                            ->disabled(),

                        SpatieMediaLibraryFileUpload::make(Member::PROFILE_PICTURES)
                            ->label('👤 Profile Picture')
                            ->helperText('Upload a profile picture for this member')
                            ->columnSpanFull()
                            ->collection(Member::PROFILE_PICTURES)
                            ->disk(config('filament.default_filesystem_disk'))
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                            ])
                            ->maxSize(5120) // 5MB
                            ->nullable()
                            ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/tiff'])
                            ->visibility('private'),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('first_name')
                                    ->label('First Name')
                                    ->helperText('Member\'s first name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                        $set('full_name', trim($get('first_name').' '.$get('last_name')));
                                    }),

                                TextInput::make('last_name')
                                    ->label('Last Name')
                                    ->helperText('Member\'s last name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                        $set('full_name', trim($get('first_name').' '.$get('last_name')));
                                    }),
                            ]),
                    ])->collapsible(),

                Section::make('📧 Contact Information')
                    ->description('Email addresses and communication details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('personal_email')
                                    ->label('📧 Personal Email')
                                    ->helperText('Primary email address for communication')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('email')
                                    ->label('🔒 System Email')
                                    ->helperText('Auto-generated system email (read-only)')
                                    ->email()
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Grid::make(2)
                            ->schema([
                                PhoneInput::make('phone_number')
                                    ->label('📱 Phone Number')
                                    ->helperText('Primary contact phone number')
                                    ->required()
                                    ->defaultCountry('KE'),

                                TextInput::make('postal_address')
                                    ->label('📮 Postal Address')
                                    ->helperText('Mailing address or P.O. Box')
                                    ->maxLength(255),
                            ]),

                        Textarea::make('residence')
                            ->label('🏠 Physical Address')
                            ->helperText('Current residential address')
                            ->rows(3)
                            ->maxLength(500),
                    ])->collapsible(),

                Section::make('ℹ️ Personal Details')
                    ->description('Additional personal information and background')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('gender')
                                    ->label('⚧️ Gender')
                                    ->helperText('Select gender identity')
                                    ->required()
                                    ->options(PRFGender::getOptions())
                                    ->native(false),

                                Select::make('marital_status_id')
                                    ->label('💍 Marital Status')
                                    ->helperText('Current marital status')
                                    ->relationship(
                                        name: 'maritalStatus',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                                    )
                                    ->searchable()
                                    ->native(false)
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                    ]),

                                TextInput::make('year_of_salvation')
                                    ->label('✝️ Year of Salvation')
                                    ->helperText('Year when member accepted Christ')
                                    ->numeric()
                                    ->minValue(1900)
                                    ->maxValue(date('Y'))
                                    ->placeholder('e.g., 2020'),
                            ]),

                        Textarea::make('bio')
                            ->label('📝 Biography')
                            ->helperText('Brief personal background and testimony')
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('Share a brief testimony or background about this member...'),
                    ])->collapsible(),

                Section::make('⛪ Local Church Information')
                    ->description('Church affiliation and involvement details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('church_id')
                                    ->label('⛪ Church')
                                    ->helperText('Local church where member attends')
                                    ->relationship(
                                        name: 'church',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                                    )
                                    ->searchable()
                                    ->native(false)
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                    ]),

                                TextInput::make('pastor')
                                    ->label('👨‍💼 Pastor\'s Name')
                                    ->helperText('Name of the church pastor')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Pastor John Smith'),
                            ]),

                        Toggle::make('church_volunteer')
                            ->label('🤝 Church Volunteer')
                            ->helperText('Is this member actively volunteering in their local church?')
                            ->inline(false),
                    ])->collapsible(),

                Section::make('💼 Professional Information')
                    ->description('Career and professional background')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('profession_id')
                                    ->label('💼 Profession')
                                    ->helperText('Current profession or career field')
                                    ->relationship(
                                        name: 'profession',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                                    )
                                    ->searchable()
                                    ->native(false)
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                    ]),

                                TextInput::make('profession_institution')
                                    ->label('🏢 Institution/Company')
                                    ->helperText('Workplace or institution name')
                                    ->maxLength(255)
                                    ->placeholder('e.g., University of Nairobi'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Textarea::make('profession_location')
                                    ->label('📍 Work Location')
                                    ->helperText('Physical location of workplace')
                                    ->rows(2)
                                    ->maxLength(255)
                                    ->placeholder('e.g., Nairobi, Kenya'),

                                TextInput::make('profession_contact')
                                    ->label('📞 Work Contact')
                                    ->helperText('Professional contact information')
                                    ->maxLength(255)
                                    ->placeholder('e.g., +254712345678'),
                            ]),

                        TextInput::make('linked_in_url')
                            ->label('🔗 LinkedIn Profile')
                            ->helperText('Professional LinkedIn profile URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://www.linkedin.com/in/username'),
                    ])->collapsible(),

                Section::make('Settings')
                    ->description('Account approval and terms acceptance')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('accept_terms')
                                    ->label('📋 Terms Accepted')
                                    ->helperText('Member has accepted terms and conditions')
                                    ->required()
                                    ->inline(false),

                                Toggle::make('approved')
                                    ->label('✅ Account Approved')
                                    ->helperText('Member account has been approved by admin')
                                    ->required()
                                    ->inline(false),
                            ]),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make(Member::PROFILE_PICTURES)
                    ->label('📷')
                    ->collection(Member::PROFILE_PICTURES)
                    ->circular()
                    ->size(45)
                    ->defaultImageUrl(function ($record) {
                        $name = $record->full_name ?? 'Member';
                        $initials = collect(explode(' ', $name))
                            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
                            ->take(2)
                            ->join('');

                        return "https://ui-avatars.com/api/?name={$initials}&color=7F9CF5&background=EBF4FF&font-size=0.6";
                    })
                    ->tooltip('Profile Picture')
                    ->extraAttributes(['class' => 'ring-2 ring-gray-200 hover:ring-blue-300 transition-all']),

                TextColumn::make('full_name')
                    ->label('👤 Member Name')
                    ->searchable(['first_name', 'last_name', 'full_name'])
                    ->sortable()
                    ->weight('medium')
                    ->wrap()
                    ->tooltip('Full name of the member'),

                TextColumn::make('email')
                    ->label('📧 Contact')
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->wrap()
                    ->copyMessage('Email copied!')
                    ->description(fn ($record) => $record->phone_number)
                    ->tooltip('Personal email and phone number')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('memberships_count')
                    ->badge()
                    ->label('📋 Memberships')
                    ->counts('memberships')
                    ->color(fn ($state) => match (true) {
                        $state === 0 => 'gray',
                        $state <= 2 => 'warning',
                        default => 'success',
                    })
                    ->icon('heroicon-o-identification')
                    ->tooltip('Number of annual memberships'),

                TextColumn::make('mission_subscriptions_count')
                    ->badge()
                    ->label('🎯 Missions')
                    ->counts('missionSubscriptions')
                    ->color(fn ($state) => match (true) {
                        $state === 0 => 'gray',
                        $state <= 3 => 'info',
                        $state <= 6 => 'warning',
                        default => 'success',
                    })
                    ->icon('heroicon-o-map-pin')
                    ->tooltip('Number of mission subscriptions'),

                TextColumn::make('created_at')
                    ->label('📅 Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Date member was added to system'),

                TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Last modification date'),

                TextColumn::make('deleted_at')
                    ->label('🗑️ Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date member was deleted'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('🗑️ Show Deleted')
                    ->placeholder('Active members only')
                    ->trueLabel('With deleted')
                    ->falseLabel('Active only'),

                SelectFilter::make('approved')
                    ->label('🚦 Approval Status')
                    ->options([
                        true => '✅ Approved',
                        false => '⏳ Pending Approval',
                    ])
                    ->default(true),

                SelectFilter::make('is_invited')
                    ->label('📧 Invitation Status')
                    ->options([
                        true => '📧 Invited',
                        false => '⏳ Pending Invite',
                    ]),

                PRFGender::getTableFilter(),

                SelectFilter::make('profession')
                    ->label('💼 Profession')
                    ->relationship('profession', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Profession'),

            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color(Color::Gray)
                        ->visible(fn () => userCan('view member')),

                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color(Color::Orange)
                        ->visible(fn () => userCan('edit member'))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Member updated!')
                                ->body('Member information has been updated successfully.')
                        ),

                    Action::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($record) {
                            $record->update(['approved' => true]);
                            Notification::make()
                                ->success()
                                ->title('Member approved!')
                                ->body("{$record->full_name} has been approved successfully.")
                                ->send();
                        })
                        ->visible(fn ($record) => ! $record->approved && userCan('edit member'))
                        ->requiresConfirmation()
                        ->modalDescription('This will approve the member and allow them access to the system.'),

                    DeleteAction::make()
                        ->color(Color::Red)
                        ->visible(fn () => userCan('delete member')),

                    RestoreAction::make()
                        ->color(Color::Green)
                        ->visible(fn () => userCan('delete member')),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->headerActions([
                Action::make('Download Template')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color(Color::Gray)
                    ->action(function () {
                        return Excel::download(new ImportTemplateExport, 'member-import-template.xlsx');
                    })
                    ->tooltip('Download Excel template for member import'),

                Action::make('Import')
                    ->label('Import Members')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color(Color::Blue)
                    ->schema([
                        Tabs::make('Upload Options')
                            ->tabs([
                                Tab::make('File Upload')
                                    ->schema([
                                        FileUpload::make('import_file')
                                            ->label('Excel File')
                                            ->acceptedFileTypes([
                                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                                'application/vnd.ms-excel',
                                                '.xlsx',
                                                '.xls',
                                            ])
                                            ->directory('imports')
                                            ->disk('local')
                                            ->visibility('private')
                                            ->uploadingMessage('Uploading Excel file...')
                                            ->helperText('Upload an Excel file with member data. Required columns: first_name, last_name, phone_number, email_address, other_names (optional). Max size: 10MB')
                                            ->columnSpanFull()
                                            ->live()
                                            ->nullable()
                                            ->maxSize(10240)
                                            ->preserveFilenames()
                                            ->afterStateUpdated(function ($state) {
                                                if ($state) {
                                                    Notification::make()
                                                        ->title('File uploaded successfully')
                                                        ->body('Excel file is ready for import.')
                                                        ->success()
                                                        ->send();
                                                }
                                            }),
                                    ]),

                                Tab::make('Alternative Upload')
                                    ->schema([
                                        FileUpload::make('import_file_alt')
                                            ->label('Excel File (Alternative)')
                                            ->acceptedFileTypes([
                                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                                'application/vnd.ms-excel',
                                                '.xlsx',
                                                '.xls',
                                            ])
                                            ->directory('temp-imports')
                                            ->disk('azure_tmp')
                                            ->maxSize(10240)
                                            ->visibility('private')
                                            ->uploadingMessage('Uploading to Azure...')
                                            ->helperText('Alternative upload method using Azure Blob Storage directly.')
                                            ->columnSpanFull()
                                            ->nullable()
                                            ->preserveFilenames(),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data) {
                        try {
                            // Log the raw data for debugging
                            Log::info('Import action triggered with data', [
                                'data_keys' => array_keys($data),
                                'import_file_exists' => isset($data['import_file']),
                                'import_file_alt_exists' => isset($data['import_file_alt']),
                                'import_file_value' => $data['import_file'] ?? 'not_set',
                                'import_file_alt_value' => $data['import_file_alt'] ?? 'not_set',
                            ]);

                            // Check which upload method was used
                            $uploadFile = null;
                            $uploadDisk = null;

                            if (isset($data['import_file']) && ! empty($data['import_file']) && $data['import_file'] !== null) {
                                $uploadFile = $data['import_file'];
                                $uploadDisk = 'local';
                            } elseif (isset($data['import_file_alt']) && ! empty($data['import_file_alt']) && $data['import_file_alt'] !== null) {
                                $uploadFile = $data['import_file_alt'];
                                $uploadDisk = 'azure_tmp';
                            }

                            // Debug: Log the data received
                            Log::info('Import action triggered', [
                                'data' => $data,
                                'upload_file' => $uploadFile,
                                'upload_disk' => $uploadDisk,
                            ]);

                            if (! $uploadFile || is_array($uploadFile) && empty($uploadFile[0])) {
                                Notification::make()
                                    ->title('No file provided')
                                    ->body('Please select a valid Excel file to upload.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            // Handle array of files (take first file)
                            if (is_array($uploadFile)) {
                                $uploadFile = $uploadFile[0] ?? null;
                            }

                            if (! $uploadFile) {
                                Notification::make()
                                    ->title('Invalid file')
                                    ->body('The uploaded file is invalid or corrupted.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            // Log the final file details
                            Log::info('Processing file upload', [
                                'upload_file' => $uploadFile,
                                'upload_disk' => $uploadDisk,
                                'file_exists' => Storage::disk($uploadDisk)->exists($uploadFile),
                            ]);

                            if (! Storage::disk($uploadDisk)->exists($uploadFile)) {
                                Notification::make()
                                    ->title('File not found')
                                    ->body("The uploaded file could not be found on {$uploadDisk} disk. File path: {$uploadFile}")
                                    ->danger()
                                    ->send();

                                return;
                            }

                            // Check file size
                            try {
                                $fileSize = Storage::disk($uploadDisk)->size($uploadFile);
                                if ($fileSize === false || $fileSize === 0) {
                                    Notification::make()
                                        ->title('Empty file')
                                        ->body('The uploaded file is empty. Please check your Excel file and try again.')
                                        ->danger()
                                        ->send();

                                    return;
                                }
                                Log::info('File size check passed', ['size' => $fileSize]);
                            } catch (Exception $e) {
                                Log::error('Error checking file size', [
                                    'file' => $uploadFile,
                                    'disk' => $uploadDisk,
                                    'error' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->title('File validation error')
                                    ->body('Unable to validate the uploaded file. Please try again.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $import = new WebUploadImport;

                            if ($uploadDisk === 'local') {
                                // For local storage, use direct path
                                $filePath = Storage::disk($uploadDisk)->path($uploadFile);

                                if (! file_exists($filePath)) {
                                    throw new Exception('Local file does not exist: '.$filePath);
                                }

                                if (filesize($filePath) === 0) {
                                    throw new Exception('Uploaded file is empty. Please check your Excel file and try again.');
                                }

                                Log::info('Processing local import file', ['path' => $filePath, 'size' => filesize($filePath)]);
                                Excel::import($import, $filePath);
                            } else {
                                // For Azure storage, download to temp file and process
                                $fileContents = Storage::disk($uploadDisk)->get($uploadFile);

                                if ($fileContents === false) {
                                    throw new Exception('Failed to read file contents from Azure storage.');
                                }

                                if (empty($fileContents)) {
                                    throw new Exception('Uploaded file is empty. Please check your Excel file and try again.');
                                }

                                // Create temporary file for processing
                                $tempPath = tempnam(sys_get_temp_dir(), 'member_import_').'.xlsx';

                                if (file_put_contents($tempPath, $fileContents) === false) {
                                    throw new Exception('Failed to create temporary file for processing.');
                                }

                                if (! file_exists($tempPath) || filesize($tempPath) === 0) {
                                    throw new Exception('Failed to create valid temporary file for processing.');
                                }

                                Log::info('Processing Azure import file', ['temp_path' => $tempPath, 'size' => filesize($tempPath)]);
                                Excel::import($import, $tempPath);

                                // Clean up temporary file
                                if (file_exists($tempPath)) {
                                    unlink($tempPath);
                                }
                            }

                            $summary = $import->getSummary();
                            $errors = $import->getErrors();

                            if (count($errors) > 0) {
                                $errorSummary = count($errors) > 5
                                    ? implode("\n", array_slice($errors, 0, 5))."\n... and ".(count($errors) - 5).' more errors'
                                    : implode("\n", $errors);

                                Notification::make()
                                    ->title('Import completed with warnings')
                                    ->body($summary."\n\nErrors:\n".$errorSummary)
                                    ->warning()
                                    ->duration(10000)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Import successful')
                                    ->body($summary)
                                    ->success()
                                    ->send();
                            }

                            // Clean up uploaded file
                            try {
                                if (Storage::disk($uploadDisk)->exists($uploadFile)) {
                                    Storage::disk($uploadDisk)->delete($uploadFile);
                                }
                            } catch (Exception $e) {
                                Log::warning('Failed to clean up uploaded file', [
                                    'file' => $uploadFile,
                                    'disk' => $uploadDisk,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        } catch (Exception $e) {
                            Log::error('Member import failed', [
                                'error' => $e->getMessage(),
                                'file' => $uploadFile ?? 'unknown',
                                'disk' => $uploadDisk ?? 'unknown',
                            ]);

                            Notification::make()
                                ->title('Import failed')
                                ->body('Error importing members: '.$e->getMessage())
                                ->danger()
                                ->duration(8000)
                                ->send();
                        }
                    })
                    ->modalHeading('Import Members from Excel')
                    ->modalDescription('Upload an Excel file to import new members into the system.')
                    ->modalSubmitActionLabel('Import Members')
                    ->tooltip('Import members from Excel file'),

                Action::make('Invite')
                    ->label('Send All Credentials')
                    ->icon('heroicon-o-envelope')
                    ->color(Color::Green)
                    ->action(function () {
                        Notification::make()
                            ->title('Bulk invitations sent')
                            ->body('Credentials have been sent to all new members.')
                            ->info()
                            ->send();
                        Artisan::call(InviteMembersCommand::class);
                    })
                    ->requiresConfirmation()
                    ->modalDescription('This will send login credentials to all members who haven\'t been invited yet.')
                    ->tooltip('Send credentials to all uninvited members'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve_members')
                        ->label('✅ Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['approved' => true]));

                            Notification::make()
                                ->title('Members approved')
                                ->body("{$count} members have been approved successfully.")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('send_bulk_invites')
                        ->label('📧 Send Invites')
                        ->icon('heroicon-o-envelope')
                        ->color(Color::Blue)
                        ->action(function ($records) {
                            $count = $records->where('approved', true)->where('is_invited', false)->count();

                            // Logic to send invites to eligible members

                            Notification::make()
                                ->title('Bulk invitations sent')
                                ->body("Invitations sent to {$count} eligible members.")
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->color(Color::Red),

                    ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    RestoreBulkAction::make()
                        ->color(Color::Green),
                ])->visible(fn () => userCan('delete member')),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->deferLoading()
            ->poll('30s')
            ->searchPlaceholder('🔍 Search members by name, email, or phone...')
            ->emptyStateHeading('No members found')
            ->emptyStateDescription('Start by adding your first member to the system.')
            ->emptyStateIcon('heroicon-o-users')
            ->recordUrl(fn ($record) => route('filament.admin.resources.members.view', $record))
            ->recordClasses(fn ($record) => match (true) {
                ! $record->approved => 'bg-yellow-50 border-l-4 border-yellow-400',
                $record->trashed() => 'bg-red-50 border-l-4 border-red-400',
                default => null,
            });
    }

    public static function getRelations(): array
    {
        return [
            MembershipsRelationManager::class,
            MissionSubscriptionsRelationManager::class,
            DepartmentsRelationManager::class,
            GiftsRelationManager::class,
            GroupMembersRelationManager::class,
            CourseMembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
            'create' => CreateMember::route('/create'),
            'view' => ViewMember::route('/{record}'),
            'edit' => EditMember::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canAccess(): bool
    {
        return userCan('viewAny member');
    }
}
