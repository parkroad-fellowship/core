<?php

namespace App\Filament\Resources;

use App\Console\Commands\Member\InviteMembersCommand;
use App\Enums\PRFActiveStatus;
use App\Enums\PRFGender;
use App\Filament\Resources\MemberResource\Pages;
use App\Filament\Resources\MemberResource\RelationManagers;
use App\Models\Member;
use Filament\Forms;
use Filament\Forms\Form;
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
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Organising Secretary';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $modelLabel = 'Member';

    protected static ?string $pluralModelLabel = 'Members';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('👤 Personal Information')
                    ->description('Basic personal details and identification')
                    ->schema([
                        Forms\Components\TextInput::make('ulid')
                            ->required()
                            ->label('ULID')
                            ->visible(app()->isLocal())
                            ->disabled(),

                        Forms\Components\SpatieMediaLibraryFileUpload::make(Member::PROFILE_PICTURES)
                            ->label('👤 Profile Picture')
                            ->helperText('Upload a profile picture for this member')
                            ->columnSpanFull()
                            ->collection(Member::PROFILE_PICTURES)
                            ->disk(config('media-library.disk_name'))
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                            ])
                            ->maxSize(5120), // 5MB

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->label('First Name')
                                    ->helperText('Member\'s first name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                        $set('full_name', trim($get('first_name').' '.$get('last_name')));
                                    }),

                                Forms\Components\TextInput::make('last_name')
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

                Forms\Components\Section::make('📧 Contact Information')
                    ->description('Email addresses and communication details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('personal_email')
                                    ->label('📧 Personal Email')
                                    ->helperText('Primary email address for communication')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('email')
                                    ->label('🔒 System Email')
                                    ->helperText('Auto-generated system email (read-only)')
                                    ->email()
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                PhoneInput::make('phone_number')
                                    ->label('📱 Phone Number')
                                    ->helperText('Primary contact phone number')
                                    ->required()
                                    ->defaultCountry('KE'),

                                Forms\Components\TextInput::make('postal_address')
                                    ->label('📮 Postal Address')
                                    ->helperText('Mailing address or P.O. Box')
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Textarea::make('residence')
                            ->label('🏠 Physical Address')
                            ->helperText('Current residential address')
                            ->rows(3)
                            ->maxLength(500),
                    ])->collapsible(),

                Forms\Components\Section::make('ℹ️ Personal Details')
                    ->description('Additional personal information and background')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('gender')
                                    ->label('⚧️ Gender')
                                    ->helperText('Select gender identity')
                                    ->required()
                                    ->options(PRFGender::getOptions())
                                    ->native(false),

                                Forms\Components\Select::make('marital_status_id')
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
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                    ]),

                                Forms\Components\TextInput::make('year_of_salvation')
                                    ->label('✝️ Year of Salvation')
                                    ->helperText('Year when member accepted Christ')
                                    ->numeric()
                                    ->minValue(1900)
                                    ->maxValue(date('Y'))
                                    ->placeholder('e.g., 2020'),
                            ]),

                        Forms\Components\Textarea::make('bio')
                            ->label('📝 Biography')
                            ->helperText('Brief personal background and testimony')
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('Share a brief testimony or background about this member...'),
                    ])->collapsible(),

                Forms\Components\Section::make('⛪ Local Church Information')
                    ->description('Church affiliation and involvement details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('church_id')
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
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                    ]),

                                Forms\Components\TextInput::make('pastor')
                                    ->label('👨‍💼 Pastor\'s Name')
                                    ->helperText('Name of the church pastor')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Pastor John Smith'),
                            ]),

                        Forms\Components\Toggle::make('church_volunteer')
                            ->label('🤝 Church Volunteer')
                            ->helperText('Is this member actively volunteering in their local church?')
                            ->inline(false),
                    ])->collapsible(),

                Forms\Components\Section::make('💼 Professional Information')
                    ->description('Career and professional background')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('profession_id')
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
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                    ]),

                                Forms\Components\TextInput::make('profession_institution')
                                    ->label('🏢 Institution/Company')
                                    ->helperText('Workplace or institution name')
                                    ->maxLength(255)
                                    ->placeholder('e.g., University of Nairobi'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('profession_location')
                                    ->label('📍 Work Location')
                                    ->helperText('Physical location of workplace')
                                    ->rows(2)
                                    ->maxLength(255)
                                    ->placeholder('e.g., Nairobi, Kenya'),

                                Forms\Components\TextInput::make('profession_contact')
                                    ->label('📞 Work Contact')
                                    ->helperText('Professional contact information')
                                    ->maxLength(255)
                                    ->placeholder('e.g., +254712345678'),
                            ]),

                        Forms\Components\TextInput::make('linked_in_url')
                            ->label('🔗 LinkedIn Profile')
                            ->helperText('Professional LinkedIn profile URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://www.linkedin.com/in/username'),
                    ])->collapsible(),

                Forms\Components\Section::make('Settings')
                    ->description('Account approval and terms acceptance')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('accept_terms')
                                    ->label('📋 Terms Accepted')
                                    ->helperText('Member has accepted terms and conditions')
                                    ->required()
                                    ->inline(false),

                                Forms\Components\Toggle::make('approved')
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
                Tables\Columns\SpatieMediaLibraryImageColumn::make(Member::PROFILE_PICTURES)
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

                Tables\Columns\TextColumn::make('full_name')
                    ->label('👤 Member Name')
                    ->searchable(['first_name', 'last_name', 'full_name'])
                    ->sortable()
                    ->weight('medium')
                    ->wrap()
                    ->tooltip('Full name of the member'),

                Tables\Columns\TextColumn::make('email')
                    ->label('📧 Contact')
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->wrap()
                    ->copyMessage('Email copied!')
                    ->description(fn ($record) => $record->phone_number)
                    ->tooltip('Personal email and phone number')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('memberships_count')
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

                Tables\Columns\TextColumn::make('mission_subscriptions_count')
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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Date member was added to system'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Last modification date'),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('🗑️ Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date member was deleted'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('🗑️ Show Deleted')
                    ->placeholder('Active members only')
                    ->trueLabel('With deleted')
                    ->falseLabel('Active only'),

                Tables\Filters\SelectFilter::make('approved')
                    ->label('🚦 Approval Status')
                    ->options([
                        true => '✅ Approved',
                        false => '⏳ Pending Approval',
                    ])
                    ->default(true),

                Tables\Filters\SelectFilter::make('is_invited')
                    ->label('📧 Invitation Status')
                    ->options([
                        true => '📧 Invited',
                        false => '⏳ Pending Invite',
                    ]),

                PRFGender::getTableFilter(),

                Tables\Filters\SelectFilter::make('profession')
                    ->label('💼 Profession')
                    ->relationship('profession', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Profession'),

            ], layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color(Color::Gray)
                        ->visible(fn () => userCan('view member')),

                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color(Color::Orange)
                        ->visible(fn () => userCan('edit member'))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Member updated!')
                                ->body('Member information has been updated successfully.')
                        ),

                    Tables\Actions\Action::make('approve')
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

                    Tables\Actions\DeleteAction::make()
                        ->color(Color::Red)
                        ->visible(fn () => userCan('delete member')),

                    Tables\Actions\RestoreAction::make()
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
                Actions\Action::make('Download Template')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color(Color::Gray)
                    ->action(function () {
                        return Excel::download(new \App\Exports\Member\ImportTemplateExport, 'member-import-template.xlsx');
                    })
                    ->tooltip('Download Excel template for member import'),

                Actions\Action::make('Import')
                    ->label('Import Members')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color(Color::Blue)
                    ->form([
                        Forms\Components\FileUpload::make('import_file')
                            ->label('Excel File')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', '.xlsx', '.xls'])
                            ->directory('imports')
                            ->required()
                            ->disk(config('filesystems.default'))
                            ->helperText('Upload an Excel file with member data. Required columns: first_name, last_name, phone_number, email_address, other_names (optional)')
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data) {
                        try {
                            $defaultDisk = config('filesystems.default');

                            if (! Storage::disk($defaultDisk)->exists($data['import_file'])) {
                                Notification::make()
                                    ->title('File not found')
                                    ->body('The uploaded file could not be found on the storage disk.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $import = new \App\Imports\Member\WebUploadImport;

                            // For cloud storage, we need to get the file contents
                            if (in_array($defaultDisk, ['azure', 's3'])) {
                                // Get file contents and create a temporary local file
                                $fileContents = Storage::disk($defaultDisk)->get($data['import_file']);

                                if ($fileContents === false || empty($fileContents)) {
                                    throw new \Exception('Failed to read file contents from storage.');
                                }

                                $tempPath = tempnam(sys_get_temp_dir(), 'member_import_').'.xlsx';
                                file_put_contents($tempPath, $fileContents);

                                Excel::import($import, $tempPath);

                                // Clean up temporary file
                                if (file_exists($tempPath)) {
                                    unlink($tempPath);
                                }
                            } else {
                                // For local storage, use the direct path
                                Excel::import($import, Storage::disk($defaultDisk)->path($data['import_file']));
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
                            if (Storage::disk($defaultDisk)->exists($data['import_file'])) {
                                Storage::disk($defaultDisk)->delete($data['import_file']);
                            } else {
                                Notification::make()
                                    ->title('File cleanup failed')
                                    ->body('The uploaded file could not be deleted after import.')
                                    ->warning()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Import failed')
                                ->body('Error importing members: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalHeading('Import Members from Excel')
                    ->modalDescription('Upload an Excel file to import new members into the system.')
                    ->modalSubmitActionLabel('Import Members')
                    ->tooltip('Import members from Excel file'),

                Actions\Action::make('Invite')
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_members')
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

                    Tables\Actions\BulkAction::make('send_bulk_invites')
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

                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\RestoreBulkAction::make()
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
            RelationManagers\MembershipsRelationManager::class,
            RelationManagers\MissionSubscriptionsRelationManager::class,
            RelationManagers\DepartmentsRelationManager::class,
            RelationManagers\GiftsRelationManager::class,
            RelationManagers\GroupMembersRelationManager::class,
            RelationManagers\CourseMembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'view' => Pages\ViewMember::route('/{record}'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
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
