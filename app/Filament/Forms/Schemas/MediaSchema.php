<?php

namespace App\Filament\Forms\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Section;

class MediaSchema
{
    /**
     * Create a complete media section with file upload(s).
     */
    public static function make(
        string $collection,
        string $sectionTitle = 'Media',
        string $sectionDescription = 'Upload photos and files',
        string $sectionIcon = 'heroicon-o-photo',
        string $label = 'Photos',
        bool $multiple = true,
        int $maxFiles = 20,
        array $acceptedFileTypes = ['image/*'],
        bool $collapsible = true,
        bool $collapsed = false,
    ): Section {
        return Section::make($sectionTitle)
            ->description($sectionDescription)
            ->icon($sectionIcon)
            ->schema([
                static::uploadField(
                    collection: $collection,
                    label: $label,
                    multiple: $multiple,
                    maxFiles: $maxFiles,
                    acceptedFileTypes: $acceptedFileTypes,
                ),
            ])
            ->collapsible($collapsible)
            ->collapsed($collapsed);
    }

    /**
     * Create a Spatie Media Library file upload field.
     */
    public static function uploadField(
        string $collection,
        string $label = 'Photos',
        bool $multiple = true,
        int $maxFiles = 20,
        array $acceptedFileTypes = ['image/*'],
        ?string $helperText = null,
    ): SpatieMediaLibraryFileUpload {
        $field = SpatieMediaLibraryFileUpload::make($collection)
            ->label($label)
            ->collection($collection)
            ->disk(config('filament.default_filesystem_disk'))
            ->acceptedFileTypes($acceptedFileTypes)
            ->columnSpanFull();

        if ($multiple) {
            $field->multiple()->maxFiles($maxFiles);
            $field->helperText($helperText ?? "Drag and drop files here or click to browse. You can upload up to {$maxFiles} files. Accepted formats: images (JPG, PNG, etc.)");
        } else {
            $field->helperText($helperText ?? 'Drag and drop a file here or click to browse.');
        }

        return $field;
    }

    /**
     * Create a poster/cover image upload field.
     */
    public static function posterField(
        string $collection,
        string $label = 'Poster/Cover Image',
        ?string $helperText = null,
    ): SpatieMediaLibraryFileUpload {
        return static::uploadField(
            collection: $collection,
            label: $label,
            multiple: false,
            acceptedFileTypes: ['image/*'],
            helperText: $helperText ?? 'Upload the main poster or cover image. This will be displayed prominently.',
        );
    }

    /**
     * Create a documents upload field.
     */
    public static function documentsField(
        string $collection,
        string $label = 'Documents',
        int $maxFiles = 10,
        ?string $helperText = null,
    ): SpatieMediaLibraryFileUpload {
        return static::uploadField(
            collection: $collection,
            label: $label,
            multiple: true,
            maxFiles: $maxFiles,
            acceptedFileTypes: [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            helperText: $helperText ?? "Upload documents (PDF, Word, Excel). Maximum {$maxFiles} files allowed.",
        );
    }
}
