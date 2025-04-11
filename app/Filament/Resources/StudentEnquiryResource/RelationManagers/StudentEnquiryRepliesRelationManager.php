<?php

namespace App\Filament\Resources\StudentEnquiryResource\RelationManagers;

use App\Enums\PRFMorphType;
use App\Models\Member;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class StudentEnquiryRepliesRelationManager extends RelationManager
{
    protected static string $relationship = 'studentEnquiryReplies';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\MorphToSelect::make('commentorable')
                    ->preload()
                    ->label('Commentor')
                    ->columnSpanFull()
                    ->types([
                        Forms\Components\MorphToSelect\Type::make(Member::class)
                            ->titleAttribute('first_name')
                            ->label('Member'),
                        Forms\Components\MorphToSelect\Type::make(Student::class)
                            ->titleAttribute('name')
                            ->label('Student'),
                    ]),
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->columns([
                Tables\Columns\TextColumn::make('content')
                    ->wrap(),
                Tables\Columns\TextColumn::make('commentorable_type')
                    ->label('Commented By')
                    ->formatStateUsing(fn ($record) => PRFMorphType::fromValue($record->commentorable_type)->name)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Replied On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
