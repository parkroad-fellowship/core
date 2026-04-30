<?php

namespace App\Filament\Forms\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class ContactSchema
{
    /**
     * Create a phone input field.
     */
    public static function phoneField(
        string $name = 'phone_number',
        string $label = 'Phone Number',
        string $defaultCountry = 'KE',
        bool $required = false,
        ?string $helperText = null,
    ): PhoneInput {
        $field = PhoneInput::make($name)
            ->label($label)
            ->defaultCountry($defaultCountry)
            ->required($required);

        if ($helperText) {
            $field->helperText($helperText);
        } else {
            $field->helperText('Enter a valid phone number with country code');
        }

        return $field;
    }

    /**
     * Create an email input field.
     */
    public static function emailField(
        string $name = 'email',
        string $label = 'Email Address',
        bool $required = false,
        ?string $helperText = null,
    ): TextInput {
        $field = TextInput::make($name)
            ->label($label)
            ->email()
            ->required($required)
            ->placeholder('e.g., john.doe@example.com');

        if ($helperText) {
            $field->helperText($helperText);
        }

        return $field;
    }

    /**
     * Create a WhatsApp link field.
     */
    public static function whatsAppField(
        string $name = 'whats_app_link',
        string $label = 'WhatsApp Group Link',
        ?string $helperText = null,
    ): TextInput {
        return TextInput::make($name)
            ->label($label)
            ->url()
            ->placeholder('https://chat.whatsapp.com/XXXXXXXXXX')
            ->helperText($helperText ?? 'Paste the WhatsApp group invite link here. Missionaries will use this to join the group for coordination.')
            ->columnSpanFull();
    }

    /**
     * Create a complete communication section.
     */
    public static function communicationSection(
        string $sectionTitle = 'Communication',
        string $sectionDescription = 'Contact information and group links',
        string $sectionIcon = 'heroicon-o-chat-bubble-left-right',
        bool $includeOfflineMembers = true,
        bool $collapsible = true,
        bool $collapsed = false,
    ): Section {
        $schema = [
            static::whatsAppField(),
        ];

        if ($includeOfflineMembers) {
            $schema[] = static::offlineMembersRepeater();
        }

        return Section::make($sectionTitle)
            ->description($sectionDescription)
            ->icon($sectionIcon)
            ->schema($schema)
            ->collapsible($collapsible)
            ->collapsed($collapsed);
    }

    /**
     * Create an offline members repeater.
     */
    public static function offlineMembersRepeater(
        string $name = 'offlineMembers',
        string $label = 'Offline Members',
    ): Repeater {
        return Repeater::make($name)
            ->relationship()
            ->label($label)
            ->schema([
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->placeholder('e.g., John Doe')
                            ->helperText('Enter the member\'s full name'),
                        static::phoneField(
                            name: 'phone',
                            required: true,
                            helperText: 'Enter their phone number for direct contact',
                        ),
                    ]),
            ])
            ->addActionLabel('Add offline member')
            ->collapsible()
            ->collapsed()
            ->defaultItems(0)
            ->columnSpanFull()
            ->helperText('Add members who don\'t have WhatsApp and need to be contacted directly by phone.');
    }

    /**
     * Create a contact info grid with phone and email.
     */
    public static function contactInfoFields(
        bool $phoneRequired = true,
        bool $emailRequired = false,
        string $defaultCountry = 'KE',
    ): Grid {
        return Grid::make(2)
            ->columnSpanFull()
            ->schema([
                static::phoneField(
                    required: $phoneRequired,
                    defaultCountry: $defaultCountry,
                    helperText: 'Primary contact phone number',
                ),
                static::emailField(
                    required: $emailRequired,
                    helperText: 'Optional email address for correspondence',
                ),
            ]);
    }
}
