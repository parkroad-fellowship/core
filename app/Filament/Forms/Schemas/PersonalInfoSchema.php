<?php

namespace App\Filament\Forms\Schemas;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFGender;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class PersonalInfoSchema
{
    /**
     * Create a profile picture upload field.
     */
    public static function profilePictureField(
        string $collection,
        string $label = 'Profile Picture',
        string $helperText = 'Upload a clear photo (recommended: square image)',
    ): SpatieMediaLibraryFileUpload {
        return SpatieMediaLibraryFileUpload::make($collection)
            ->label($label)
            ->helperText($helperText)
            ->columnSpanFull()
            ->collection($collection)
            ->disk(config('filament.default_filesystem_disk'))
            ->image()
            ->imageEditor()
            ->imageAspectRatio(1, 1)
            ->maxSize(5120)
            ->nullable()
            ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/tiff']);
    }

    /**
     * Create name fields (first name, last name) with auto full name generation.
     */
    public static function nameFields(
        bool $generateFullName = true,
        string $fullNameField = 'full_name',
    ): Grid {
        $firstNameField = TextInput::make('first_name')
            ->label('First Name')
            ->helperText('Enter first name')
            ->required()
            ->maxLength(255)
            ->placeholder('e.g., John');

        $lastNameField = TextInput::make('last_name')
            ->label('Last Name')
            ->helperText('Enter last name')
            ->required()
            ->maxLength(255)
            ->placeholder('e.g., Doe');

        if ($generateFullName) {
            $firstNameField->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, $get) use ($fullNameField) {
                    $set($fullNameField, trim($get('first_name').' '.$get('last_name')));
                });

            $lastNameField->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, $get) use ($fullNameField) {
                    $set($fullNameField, trim($get('first_name').' '.$get('last_name')));
                });
        }

        return Grid::make(2)
            ->columnSpanFull()
            ->schema([
                $firstNameField,
                $lastNameField,
            ]);
    }

    /**
     * Create a single full name field.
     */
    public static function fullNameField(
        string $name = 'name',
        string $label = 'Full Name',
        bool $required = true,
    ): TextInput {
        return TextInput::make($name)
            ->label($label)
            ->helperText('Enter full name')
            ->required($required)
            ->maxLength(255)
            ->placeholder('e.g., John Doe')
            ->autocapitalize();
    }

    /**
     * Create a title/position field.
     */
    public static function titleField(
        string $name = 'title',
        string $label = 'Title/Position',
        bool $required = false,
    ): TextInput {
        return TextInput::make($name)
            ->label($label)
            ->helperText('Professional or organizational title')
            ->required($required)
            ->maxLength(255)
            ->placeholder('e.g., Senior Pastor, Director');
    }

    /**
     * Create a gender select field.
     */
    public static function genderField(
        string $name = 'gender',
        string $label = 'Gender',
        bool $required = true,
    ): Select {
        return Select::make($name)
            ->label($label)
            ->helperText('Select gender')
            ->required($required)
            ->options(PRFGender::getOptions())
            ->native(false)
            ->placeholder('Choose gender...');
    }

    /**
     * Create a marital status relationship field.
     */
    public static function maritalStatusField(
        string $name = 'marital_status_id',
        string $label = 'Marital Status',
        bool $required = false,
    ): Select {
        return Select::make($name)
            ->label($label)
            ->helperText('Current marital status')
            ->required($required)
            ->relationship(
                name: 'maritalStatus',
                titleAttribute: 'name',
                modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
            )
            ->searchable()
            ->preload()
            ->native(false)
            ->placeholder('Select status...');
    }

    /**
     * Create a biography/about text area.
     */
    public static function bioField(
        string $name = 'bio',
        string $label = 'Biography',
        int $rows = 4,
        int $maxLength = 2000,
        bool $required = false,
    ): Textarea {
        return Textarea::make($name)
            ->label($label)
            ->helperText("Brief background information (max {$maxLength} characters)")
            ->rows($rows)
            ->maxLength($maxLength)
            ->required($required)
            ->placeholder('Write a brief biography or description...')
            ->hint("Maximum {$maxLength} characters")
            ->hintColor('gray');
    }

    /**
     * Create a complete personal information section.
     */
    public static function make(
        string $sectionTitle = 'Personal Information',
        string $sectionDescription = 'Basic personal details',
        string $sectionIcon = 'heroicon-o-user',
        ?string $profilePictureCollection = null,
        bool $includeBio = false,
        bool $includeGender = true,
        bool $includeMaritalStatus = false,
        bool $collapsible = true,
    ): Section {
        $schema = [];

        if ($profilePictureCollection) {
            $schema[] = static::profilePictureField($profilePictureCollection);
        }

        $schema[] = static::nameFields();

        if ($includeGender || $includeMaritalStatus) {
            $genderSchema = [];
            if ($includeGender) {
                $genderSchema[] = static::genderField();
            }
            if ($includeMaritalStatus) {
                $genderSchema[] = static::maritalStatusField();
            }
            $schema[] = Grid::make(count($genderSchema))->schema($genderSchema);
        }

        if ($includeBio) {
            $schema[] = static::bioField();
        }

        return Section::make($sectionTitle)
            ->description($sectionDescription)
            ->icon($sectionIcon)
            ->schema($schema)
            ->collapsible($collapsible);
    }

    /**
     * Create a contact information section.
     */
    public static function contactSection(
        string $sectionTitle = 'Contact Information',
        string $sectionDescription = 'How to reach this person',
        string $sectionIcon = 'heroicon-o-phone',
        bool $emailRequired = false,
        bool $phoneRequired = true,
        bool $includeAddress = false,
        bool $collapsible = true,
    ): Section {
        $schema = [
            Grid::make(2)->columnSpanFull()->schema([
                ContactSchema::phoneField(required: $phoneRequired),
                ContactSchema::emailField(required: $emailRequired),
            ]),
        ];

        if ($includeAddress) {
            $schema[] = Textarea::make('residence')
                ->label('Physical Address')
                ->helperText('Current residential or office address')
                ->rows(2)
                ->maxLength(500)
                ->placeholder('Enter address...');
        }

        return Section::make($sectionTitle)
            ->description($sectionDescription)
            ->icon($sectionIcon)
            ->schema($schema)
            ->collapsible($collapsible);
    }
}
