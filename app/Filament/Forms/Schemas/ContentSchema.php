<?php

namespace App\Filament\Forms\Schemas;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class ContentSchema
{
    /**
     * Create a title field with clear labeling.
     */
    public static function titleField(
        string $name = 'title',
        string $label = 'Title',
        string $placeholder = 'Enter a descriptive title...',
        bool $required = true,
        int $maxLength = 255,
        ?string $helperText = null,
    ): TextInput {
        return TextInput::make($name)
            ->label($label)
            ->required($required)
            ->maxLength($maxLength)
            ->placeholder($placeholder)
            ->helperText($helperText ?? 'A clear and descriptive title');
    }

    /**
     * Create a name field (alias for title with different defaults).
     */
    public static function nameField(
        string $name = 'name',
        string $label = 'Name',
        string $placeholder = 'Enter name...',
        bool $required = true,
        int $maxLength = 255,
        ?string $helperText = null,
    ): TextInput {
        return static::titleField(
            name: $name,
            label: $label,
            placeholder: $placeholder,
            required: $required,
            maxLength: $maxLength,
            helperText: $helperText ?? 'Enter a unique name',
        );
    }

    /**
     * Create a description textarea.
     */
    public static function descriptionField(
        string $name = 'description',
        string $label = 'Description',
        int $rows = 4,
        bool $required = false,
        ?string $placeholder = null,
        ?string $helperText = null,
    ): Textarea {
        return Textarea::make($name)
            ->label($label)
            ->required($required)
            ->rows($rows)
            ->placeholder($placeholder ?? 'Provide a detailed description...')
            ->helperText($helperText ?? 'Add any relevant details or context')
            ->columnSpanFull();
    }

    /**
     * Create a rich text editor.
     */
    public static function richEditorField(
        string $name = 'content',
        string $label = 'Content',
        bool $required = true,
        ?string $helperText = null,
        array $toolbarButtons = [
            'bold',
            'italic',
            'underline',
            'bulletList',
            'orderedList',
            'link',
            'h2',
            'h3',
        ],
    ): RichEditor {
        return RichEditor::make($name)
            ->label($label)
            ->required($required)
            ->columnSpanFull()
            ->helperText($helperText ?? 'Write your content using the formatting tools above')
            ->toolbarButtons($toolbarButtons);
    }

    /**
     * Create a markdown editor.
     */
    public static function markdownField(
        string $name = 'content',
        string $label = 'Content',
        bool $required = false,
        ?string $helperText = null,
        ?string $placeholder = null,
        array $toolbarButtons = [
            'bold',
            'italic',
            'link',
            'bulletList',
            'orderedList',
            'h2',
            'h3',
        ],
    ): MarkdownEditor {
        return MarkdownEditor::make($name)
            ->label($label)
            ->required($required)
            ->columnSpanFull()
            ->placeholder($placeholder ?? 'Write your content here...')
            ->helperText($helperText ?? 'You can use markdown formatting')
            ->toolbarButtons($toolbarButtons);
    }

    /**
     * Create a theme/topic field.
     */
    public static function themeField(
        string $name = 'theme',
        string $label = 'Theme/Topic',
        bool $required = true,
        int $rows = 2,
    ): Textarea {
        return Textarea::make($name)
            ->label($label)
            ->required($required)
            ->rows($rows)
            ->placeholder('Enter the main theme or topic...')
            ->helperText('The central theme or focus of this item')
            ->columnSpanFull();
    }

    /**
     * Create a notes field.
     */
    public static function notesField(
        string $name = 'notes',
        string $label = 'Notes',
        int $rows = 3,
        bool $required = false,
        ?string $placeholder = null,
    ): Textarea {
        return Textarea::make($name)
            ->label($label)
            ->required($required)
            ->rows($rows)
            ->placeholder($placeholder ?? 'Add any additional notes...')
            ->helperText('Optional notes or comments')
            ->columnSpanFull();
    }

    /**
     * Create a content section with rich editor.
     */
    public static function make(
        string $sectionTitle = 'Content',
        string $sectionDescription = 'Main content for this item',
        string $sectionIcon = 'heroicon-o-document-text',
        string $editorType = 'rich',
        string $fieldName = 'content',
        string $fieldLabel = 'Content',
        bool $required = true,
        bool $collapsible = true,
    ): Section {
        $editor = match ($editorType) {
            'markdown' => static::markdownField($fieldName, $fieldLabel, $required),
            default => static::richEditorField($fieldName, $fieldLabel, $required),
        };

        return Section::make($sectionTitle)
            ->description($sectionDescription)
            ->icon($sectionIcon)
            ->schema([$editor])
            ->collapsible($collapsible);
    }

    /**
     * Create a title and description section.
     */
    public static function basicInfoSection(
        string $sectionTitle = 'Basic Information',
        string $sectionDescription = 'Essential details',
        string $sectionIcon = 'heroicon-o-information-circle',
        string $titleFieldName = 'name',
        string $titleLabel = 'Name',
        string $descriptionFieldName = 'description',
        string $descriptionLabel = 'Description',
        bool $titleRequired = true,
        bool $descriptionRequired = false,
        bool $collapsible = true,
    ): Section {
        return Section::make($sectionTitle)
            ->description($sectionDescription)
            ->icon($sectionIcon)
            ->schema([
                static::nameField(
                    name: $titleFieldName,
                    label: $titleLabel,
                    required: $titleRequired,
                ),
                static::descriptionField(
                    name: $descriptionFieldName,
                    label: $descriptionLabel,
                    required: $descriptionRequired,
                ),
            ])
            ->collapsible($collapsible);
    }
}
