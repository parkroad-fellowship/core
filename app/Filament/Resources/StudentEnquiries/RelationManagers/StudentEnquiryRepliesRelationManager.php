<?php

namespace App\Filament\Resources\StudentEnquiries\RelationManagers;

use App\Enums\PRFMorphType;
use App\Models\Member;
use App\Models\Student;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class StudentEnquiryRepliesRelationManager extends RelationManager
{
    protected static string $relationship = 'studentEnquiryReplies';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                MorphToSelect::make('commentorable')
                    ->preload()
                    ->label('Commentor')
                    ->columnSpanFull()
                    ->types([
                        Type::make(Member::class)
                            ->titleAttribute('full_name')
                            ->label('Member'),
                        Type::make(Student::class)
                            ->titleAttribute('name')
                            ->label('Student'),
                    ]),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->columns([
                TextColumn::make('content')
                    ->wrap(),
                TextColumn::make('commentorable_type')
                    ->label('Commented By')
                    ->formatStateUsing(fn ($record) => PRFMorphType::fromValue($record->commentorable_type)->name)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Replied On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
