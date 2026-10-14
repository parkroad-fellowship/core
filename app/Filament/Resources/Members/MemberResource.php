<?php

namespace App\Filament\Resources\Members;

use App\Contracts\Services\WorkspaceDirectoryInterface;
use App\Enums\PRFActiveStatus;
use App\Enums\PRFGender;
use App\Enums\PRFMemberEmailMode;
use App\Enums\PRFWorkspaceStatus;
use App\Exports\Member\ImportTemplateExport;
use App\Filament\Forms\Schemas\ContactSchema;
use App\Filament\Forms\Schemas\PersonalInfoSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\Members\Pages\CreateMember;
use App\Filament\Resources\Members\Pages\EditMember;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Filament\Resources\Members\Pages\ViewMember;
use App\Filament\Resources\Members\RelationManagers\CourseMembersRelationManager;
use App\Filament\Resources\Members\RelationManagers\DepartmentsRelationManager;
use App\Filament\Resources\Members\RelationManagers\GiftsRelationManager;
use App\Filament\Resources\Members\RelationManagers\GroupMembersRelationManager;
use App\Filament\Resources\Members\RelationManagers\MembershipsRelationManager;
use App\Filament\Resources\Members\RelationManagers\MissionSubscriptionsRelationManager;
use App\Helpers\Utils;
use App\Imports\Member\WebUploadImport;
use App\Jobs\Member\ProvisionWorkspaceAccountJob;
use App\Models\Member;
use App\Notifications\Member\MemberSignInInvitedNotification;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Unique;
use Maatwebsite\Excel\Facades\Excel;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Organising Secretary';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $modelLabel = 'Member';

    protected static ?string $pluralModelLabel = 'Members';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Personal Information')
                ->columnSpanFull()
                ->description('Enter the member\'s basic personal details such as name and photo')
                ->icon('heroicon-o-user')
                ->schema([
                    TextInput::make('ulid')
                        ->label('ULID')
                        ->helperText('Unique identifier for this member (auto-generated)')
                        ->visible(app()->isLocal())
                        ->disabled(),

                    PersonalInfoSchema::profilePictureField(
                        collection: Member::PROFILE_PICTURES,
                        label: 'Profile Picture',
                        helperText: 'Upload a photo of the member. Square images work best. Accepted formats: JPEG, PNG, TIFF. Maximum size: 5MB.',
                    ),

                    PersonalInfoSchema::nameFields(generateFullName: true, fullNameField: 'full_name'),
                ])
                ->collapsible(),

            Section::make('Contact Information')
                ->columnSpanFull()
                ->description('How to reach this member by phone, email, or mail')
                ->icon('heroicon-o-phone')
                ->schema([
                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            ContactSchema::emailField(
                                name: 'personal_email',
                                label: self::usesPersonalEmail() ? 'Email (used to sign in)' : 'Personal Email',
                                required: true,
                                helperText: self::usesPersonalEmail()
                                    ? 'The member signs in with Google using this address'
                                    : 'Where the member receives their account details and personal communication',
                            )
                                ->placeholder('e.g., john@example.com')
                                ->unique(
                                    table: 'members',
                                    column: 'personal_email',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn(Unique $rule) => $rule->where('tenant_id', tenant('id')),
                                ),

                            TextInput::make('email')
                                ->label('Organisation Email')
                                ->helperText('Created automatically in your Google Workspace and cannot be changed')
                                ->email()
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('Assigned after saving')
                                ->visible(fn() => !self::usesPersonalEmail()),
                        ]),

                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            ContactSchema::phoneField(
                                name: 'phone_number',
                                label: 'Phone Number',
                                defaultCountry: 'KE',
                                required: true,
                                helperText: 'Primary mobile or landline number. Select country code from the dropdown.',
                            ),

                            TextInput::make('postal_address')
                                ->label('Postal Address')
                                ->helperText('Mailing address or P.O. Box number for physical correspondence')
                                ->maxLength(255)
                                ->placeholder('e.g., P.O. Box 12345, Nairobi'),
                        ]),

                    Textarea::make('residence')
                        ->label('Physical Address')
                        ->helperText('The member\'s current home or residential address')
                        ->rows(3)
                        ->maxLength(500)
                        ->placeholder('e.g., Apartment 4B, Sunrise Apartments, Westlands, Nairobi'),
                ])
                ->collapsible(),

            Section::make('Personal Details')
                ->columnSpanFull()
                ->description('Additional background information about the member')
                ->icon('heroicon-o-identification')
                ->schema([
                    Grid::make(3)
                        ->columnSpanFull()
                        ->schema([
                            PersonalInfoSchema::genderField(
                                name: 'gender',
                                label: 'Gender',
                                required: true,
                            )->helperText('Select the member\'s gender'),

                            PersonalInfoSchema::maritalStatusField(
                                name: 'marital_status_id',
                                label: 'Marital Status',
                                required: false,
                            )
                                ->helperText('Select the member\'s current marital status')
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->label('Status Name')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('e.g., Single, Married, Divorced'),
                                ]),

                            TextInput::make('year_of_salvation')
                                ->label('Year of Salvation')
                                ->helperText('The year when the member accepted Christ as their Savior')
                                ->numeric()
                                ->minValue(1900)
                                ->maxValue(date('Y'))
                                ->placeholder('e.g., 2015'),
                        ]),

                    Textarea::make('bio')
                        ->label('Biography')
                        ->helperText(
                            'A brief description of the member\'s background, testimony, or any relevant personal information',
                        )
                        ->rows(4)
                        ->maxLength(1000)
                        ->placeholder(
                            'e.g., John has been a faithful member since 2015. He serves in the worship team and has a passion for youth ministry...',
                        ),
                ])
                ->collapsible(),

            Section::make('Local Church Information')
                ->columnSpanFull()
                ->description('Details about the member\'s church affiliation and involvement')
                ->icon('heroicon-o-building-library')
                ->schema([
                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            StatusSchema::relationshipSelect(
                                name: 'church_id',
                                label: 'Church',
                                relationship: 'church',
                                titleAttribute: 'name',
                                required: false,
                                searchable: true,
                                preload: true,
                                modifyQuery: fn($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                                helperText: 'Select the local church where this member attends regularly',
                            )
                                ->placeholder('Start typing to search for a church...')
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->label('Church Name')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('e.g., Grace Community Church'),
                                ]),

                            TextInput::make('pastor')
                                ->label('Pastor\'s Name')
                                ->helperText('The name of the senior pastor or church leader')
                                ->maxLength(255)
                                ->placeholder('e.g., Pastor John Smith'),
                        ]),

                    Toggle::make('church_volunteer')
                        ->label('Church Volunteer')
                        ->helperText('Check this box if the member actively volunteers or serves in their local church')
                        ->inline(false),
                ])
                ->collapsible(),

            Section::make('Professional Information')
                ->columnSpanFull()
                ->description('Career and workplace details for networking and ministry purposes')
                ->icon('heroicon-o-briefcase')
                ->schema([
                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            StatusSchema::relationshipSelect(
                                name: 'profession_id',
                                label: 'Profession',
                                relationship: 'profession',
                                titleAttribute: 'name',
                                required: false,
                                searchable: true,
                                preload: true,
                                modifyQuery: fn($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                                helperText: 'Select the member\'s current profession or career field',
                            )
                                ->placeholder('Start typing to search professions...')
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->label('Profession Name')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('e.g., Software Engineer, Teacher, Doctor'),
                                ]),

                            TextInput::make('profession_institution')
                                ->label('Institution or Company')
                                ->helperText(
                                    'The name of the organization, company, or institution where the member works',
                                )
                                ->maxLength(255)
                                ->placeholder('e.g., University of Nairobi, Safaricom Ltd'),
                        ]),

                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            Textarea::make('profession_location')
                                ->label('Work Location')
                                ->helperText('The physical address or area where the member works')
                                ->rows(2)
                                ->maxLength(255)
                                ->placeholder('e.g., Upper Hill, Nairobi, Kenya'),

                            TextInput::make('profession_contact')
                                ->label('Work Contact')
                                ->helperText(
                                    'Professional phone number or alternative contact for work-related matters',
                                )
                                ->maxLength(255)
                                ->placeholder('e.g., +254 20 123 4567'),
                        ]),

                    TextInput::make('linked_in_url')
                        ->label('LinkedIn Profile')
                        ->helperText('Link to the member\'s LinkedIn profile for professional networking')
                        ->url()
                        ->maxLength(255)
                        ->placeholder('e.g., https://www.linkedin.com/in/johndoe')
                        ->suffixIcon('heroicon-o-link'),
                ])
                ->collapsible(),

            Section::make('Account Settings')
                ->columnSpanFull()
                ->description('Administrative settings for the member\'s account status and permissions')
                ->icon('heroicon-o-cog-6-tooth')
                ->schema([
                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            Toggle::make('accept_terms')
                                ->label('Terms Accepted')
                                ->helperText(
                                    'Indicates whether the member has read and agreed to the terms and conditions',
                                )
                                ->required()
                                ->inline(false),

                            Toggle::make('approved')
                                ->label('Account Approved')
                                ->helperText(
                                    'Enable this to allow the member to access the system. New members typically require admin approval.',
                                )
                                ->required()
                                ->inline(false),
                        ]),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make(Member::PROFILE_PICTURES)
                    ->label('Photo')
                    ->collection(Member::PROFILE_PICTURES)
                    ->circular()
                    ->size(45)
                    ->defaultImageUrl(function ($record) {
                        $name = $record->full_name ?? 'Member';
                        $initials = collect(explode(' ', $name))
                            ->map(fn($word) => strtoupper(substr($word, 0, 1)))
                            ->take(2)
                            ->join('');

                        return "https://ui-avatars.com/api/?name={$initials}&color=7F9CF5&background=EBF4FF&font-size=0.6";
                    })
                    ->tooltip('Profile Picture')
                    ->extraAttributes(['class' => 'ring-2 ring-gray-200 hover:ring-blue-300 transition-all']),

                TextColumn::make('full_name')
                    ->label('Member Name')
                    ->searchable(['first_name', 'last_name', 'full_name'])
                    ->sortable()
                    ->weight('medium')
                    ->wrap()
                    ->tooltip('Full name of the member'),

                TextColumn::make(self::usesPersonalEmail() ? 'personal_email' : 'email')
                    ->label('Contact')
                    ->searchable(['email', 'personal_email'])
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->wrap()
                    ->copyMessage('Email copied!')
                    ->description(fn($record) => $record->phone_number)
                    ->tooltip('Sign-in email and phone number')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('workspace_status')
                    ->label('Workspace')
                    ->badge()
                    ->formatStateUsing(fn(?PRFWorkspaceStatus $state) => $state?->getLabel())
                    ->color(fn(?PRFWorkspaceStatus $state) => $state?->getColor() ?? 'gray')
                    ->tooltip(fn(Member $record) => $record->workspace_error)
                    ->visible(fn() => !self::usesPersonalEmail())
                    ->toggleable(),

                TextColumn::make('memberships_count')
                    ->badge()
                    ->label('Memberships')
                    ->counts('memberships')
                    ->color(fn($state) => match (true) {
                        $state === 0 => 'gray',
                        $state <= 2 => 'warning',
                        default => 'success',
                    })
                    ->icon('heroicon-o-identification')
                    ->tooltip('Number of annual memberships'),

                TextColumn::make('mission_subscriptions_count')
                    ->badge()
                    ->label('Missions')
                    ->counts('missionSubscriptions')
                    ->color(fn($state) => match (true) {
                        $state === 0 => 'gray',
                        $state <= 3 => 'info',
                        $state <= 6 => 'warning',
                        default => 'success',
                    })
                    ->icon('heroicon-o-map-pin')
                    ->tooltip('Number of mission subscriptions'),

                TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Date member was added to system'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Last modification date'),

                TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date member was deleted'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Show Deleted')
                    ->placeholder('Active members only')
                    ->trueLabel('With deleted')
                    ->falseLabel('Active only'),

                SelectFilter::make('workspace_status')
                    ->label('Workspace')
                    ->options(PRFWorkspaceStatus::getOptions())
                    ->visible(fn() => !self::usesPersonalEmail()),

                SelectFilter::make('approved')
                    ->label('Approval Status')
                    ->options([
                        true => 'Approved',
                        false => 'Pending Approval',
                    ])
                    ->default(true),

                SelectFilter::make('is_invited')
                    ->label('Invitation Status')
                    ->options([
                        true => 'Invited',
                        false => 'Pending Invite',
                    ]),

                PRFGender::getTableFilter(),

                SelectFilter::make('profession')
                    ->label('Profession')
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
                        ->visible(fn() => userCan(Member::permission('view'))),

                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color(Color::Orange)
                        ->visible(fn() => userCan(Member::permission('edit')))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Member updated!')
                                ->body('Member information has been updated successfully.'),
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
                        ->visible(fn($record) => !$record->approved && userCan(Member::permission('edit')))
                        ->requiresConfirmation()
                        ->modalDescription('This will approve the member and allow them access to the system.'),

                    Action::make('retryWorkspace')
                        ->label('Retry Workspace account')
                        ->icon('heroicon-o-arrow-path')
                        ->color(Color::Orange)
                        ->visible(
                            fn(Member $record) => (
                                !self::usesPersonalEmail()
                                && in_array(
                                    $record->workspace_status,
                                    [PRFWorkspaceStatus::PENDING, PRFWorkspaceStatus::FAILED],
                                    true,
                                )
                                && userCan(Member::permission('edit'))
                            ),
                        )
                        ->requiresConfirmation()
                        ->action(function (Member $record): void {
                            $record->update([
                                'workspace_status' => PRFWorkspaceStatus::PENDING,
                                'workspace_error' => null,
                            ]);
                            ProvisionWorkspaceAccountJob::dispatch($record);

                            Notification::make()->success()->title('Workspace account queued')->send();
                        }),

                    Action::make('linkWorkspace')
                        ->label('Link existing Workspace account')
                        ->icon('heroicon-o-link')
                        ->color(Color::Gray)
                        ->visible(
                            fn(Member $record) => (
                                !self::usesPersonalEmail()
                                && $record->workspace_status !== PRFWorkspaceStatus::PROVISIONED
                                && userCan(Member::permission('edit'))
                            ),
                        )
                        ->modalDescription('Use this when the mailbox was created by hand in Google Workspace.')
                        ->action(function (Member $record, WorkspaceDirectoryInterface $directory): void {
                            $workspaceUser = $directory->find((string) $record->email);

                            if ($workspaceUser === null) {
                                Notification::make()
                                    ->danger()
                                    ->title("No Workspace account found for {$record->email}")
                                    ->send();

                                return;
                            }

                            $record->update([
                                'workspace_user_id' => $workspaceUser->id,
                                'workspace_status' => PRFWorkspaceStatus::PROVISIONED,
                                'workspace_error' => null,
                                'workspace_provisioned_at' => now(),
                            ]);

                            Notification::make()->success()->title('Workspace account linked')->send();
                        }),

                    Action::make('sendSignInInvite')
                        ->label('Send sign-in invite')
                        ->icon('heroicon-o-envelope')
                        ->color(Color::Blue)
                        ->visible(fn() => self::usesPersonalEmail() && userCan(Member::permission('edit')))
                        ->requiresConfirmation()
                        ->action(function (Member $record): void {
                            \Illuminate\Support\Facades\Notification::route('mail', $record->personal_email)->notify(
                                new MemberSignInInvitedNotification($record),
                            );
                            $record->update(['is_invited' => true]);

                            Notification::make()->success()->title('Invite sent')->send();
                        }),

                    DeleteAction::make()
                        ->color(Color::Red)
                        ->visible(fn() => userCan(Member::permission('delete'))),

                    RestoreAction::make()
                        ->color(Color::Green)
                        ->visible(fn() => userCan(Member::permission('delete'))),
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
                        return Excel::download(new ImportTemplateExport(), 'member-import-template.xlsx');
                    })
                    ->tooltip('Download Excel template for member import'),

                Action::make('Import')
                    ->label('Import Members')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color(Color::Blue)
                    ->schema([
                        Tabs::make('Upload Options')
                            ->tabs([
                                Tab::make('File Upload')->schema([
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
                                        ->uploadingMessage('Uploading Excel file...')
                                        ->helperText(
                                            'Upload an Excel file with member data. Required columns: first_name, last_name, phone_number, email_address, other_names (optional). Max size: 10MB',
                                        )
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

                                Tab::make('Alternative Upload')->schema([
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

                            if (
                                isset($data['import_file'])
                                && !empty($data['import_file'])
                                && $data['import_file'] !== null
                            ) {
                                $uploadFile = $data['import_file'];
                                $uploadDisk = 'local';
                            } elseif (
                                isset($data['import_file_alt'])
                                && !empty($data['import_file_alt'])
                                && $data['import_file_alt'] !== null
                            ) {
                                $uploadFile = $data['import_file_alt'];
                                $uploadDisk = 'azure_tmp';
                            }

                            // Debug: Log the data received
                            Log::info('Import action triggered', [
                                'data' => $data,
                                'upload_file' => $uploadFile,
                                'upload_disk' => $uploadDisk,
                            ]);

                            if (!$uploadFile || is_array($uploadFile) && empty($uploadFile[0])) {
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

                            if (!$uploadFile) {
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

                            if (!Storage::disk($uploadDisk)->exists($uploadFile)) {
                                Notification::make()
                                    ->title('File not found')
                                    ->body(
                                        "The uploaded file could not be found on {$uploadDisk} disk. File path: {$uploadFile}",
                                    )
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
                                        ->body(
                                            'The uploaded file is empty. Please check your Excel file and try again.',
                                        )
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

                            $import = new WebUploadImport();

                            if ($uploadDisk === 'local') {
                                // For local storage, use direct path
                                $filePath = Storage::disk($uploadDisk)->path($uploadFile);

                                if (!file_exists($filePath)) {
                                    throw new Exception('Local file does not exist: ' . $filePath);
                                }

                                if (filesize($filePath) === 0) {
                                    throw new Exception(
                                        'Uploaded file is empty. Please check your Excel file and try again.',
                                    );
                                }

                                Log::info('Processing local import file', [
                                    'path' => $filePath,
                                    'size' => filesize($filePath),
                                ]);
                                Excel::import($import, $filePath);
                            } else {
                                // For Azure storage, download to temp file and process
                                $fileContents = Storage::disk($uploadDisk)->get($uploadFile);

                                if ($fileContents === false) {
                                    throw new Exception('Failed to read file contents from Azure storage.');
                                }

                                if (empty($fileContents)) {
                                    throw new Exception(
                                        'Uploaded file is empty. Please check your Excel file and try again.',
                                    );
                                }

                                // Create temporary file for processing
                                $tempPath = tempnam(sys_get_temp_dir(), 'member_import_') . '.xlsx';

                                if (file_put_contents($tempPath, $fileContents) === false) {
                                    throw new Exception('Failed to create temporary file for processing.');
                                }

                                if (!file_exists($tempPath) || filesize($tempPath) === 0) {
                                    throw new Exception('Failed to create valid temporary file for processing.');
                                }

                                Log::info('Processing Azure import file', [
                                    'temp_path' => $tempPath,
                                    'size' => filesize($tempPath),
                                ]);
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
                                    ? implode("\n", array_slice($errors, 0, 5))
                                    . "\n... and "
                                    . (count($errors) - 5)
                                    . ' more errors'
                                    : implode("\n", $errors);

                                Notification::make()
                                    ->title('Import completed with warnings')
                                    ->body($summary . "\n\nErrors:\n" . $errorSummary)
                                    ->warning()
                                    ->duration(10000)
                                    ->send();
                            } else {
                                Notification::make()->title('Import successful')->body($summary)->success()->send();
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
                                ->body('Error importing members: ' . $e->getMessage())
                                ->danger()
                                ->duration(8000)
                                ->send();
                        }
                    })
                    ->modalHeading('Import Members from Excel')
                    ->modalDescription('Upload an Excel file to import new members into the system.')
                    ->modalSubmitActionLabel('Import Members')
                    ->tooltip('Import members from Excel file'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve_members')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn($record) => $record->update(['approved' => true]));

                            Notification::make()
                                ->title('Members approved')
                                ->body("{$count} members have been approved successfully.")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('send_bulk_invites')
                        ->label('Send sign-in invites')
                        ->icon('heroicon-o-envelope')
                        ->color(Color::Blue)
                        ->visible(fn() => self::usesPersonalEmail())
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $eligible = $records->filter(
                                fn(Member $member) => $member->approved && filled($member->personal_email),
                            );

                            $eligible->each(function (Member $member): void {
                                \Illuminate\Support\Facades\Notification::route(
                                    'mail',
                                    $member->personal_email,
                                )->notify(new MemberSignInInvitedNotification($member));
                                $member->update(['is_invited' => true]);
                            });

                            Notification::make()
                                ->title('Invites sent')
                                ->body("Sign-in invites sent to {$eligible->count()} approved members.")
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make()->color(Color::Red),

                    ForceDeleteBulkAction::make()->color(Color::Red),

                    RestoreBulkAction::make()->color(Color::Green),
                ])->visible(fn() => userCan(Member::permission('delete'))),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->deferLoading()
            ->poll('30s')
            ->searchPlaceholder('Search members by name, email, or phone...')
            ->emptyStateHeading('No members found')
            ->emptyStateDescription('Start by adding your first member to the system.')
            ->emptyStateIcon('heroicon-o-users')
            ->recordUrl(fn($record) => route('filament.admin.resources.members.view', $record))
            ->recordClasses(fn($record) => match (true) {
                !$record->approved => 'bg-yellow-50 border-l-4 border-yellow-400',
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
        return userCan(Member::permission('viewAny'));
    }

    private static function usesPersonalEmail(): bool
    {
        return Utils::memberEmailMode() === PRFMemberEmailMode::PERSONAL;
    }
}
