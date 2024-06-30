<?php

namespace App\Filament\Resources\StudentEnquiryResource\RelationManagers;

use App\Models\Member;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentEnquiryRepliesRelationManager extends RelationManager
{
    protected static string $relationship = 'studentEnquiryReplies';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\MorphToSelect::make('commentorable')
                    ->preload()
                    ->label('Moderator')
                    ->columnSpanFull()
                    ->types([
                        Forms\Components\MorphToSelect\Type::make(Member::class)
                            ->titleAttribute('first_name')
                            ->label('Member'),
                    ]),
                Forms\Components\TextArea::make('content')
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Replied On')
                    ->dateTime()
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
