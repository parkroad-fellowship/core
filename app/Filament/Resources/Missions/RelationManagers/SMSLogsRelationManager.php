<?php

namespace App\Filament\Resources\Missions\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SMSLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'smsLogs';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $title = 'Text messages sent';

    protected static ?string $label = 'SMS Log';

    protected static ?string $pluralLabel = 'SMS Logs';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->smsLogs()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('SMS Details')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('phone')->label('Phone Number')->disabled(),
                    TextInput::make('message_id')->label('Reference')->disabled(),
                    Toggle::make('is_blacklisted')->label('Blacklisted')->disabled(),
                    Textarea::make('message')->label('Message Content')->rows(4)->disabled()->columnSpanFull(),
                    KeyValue::make('response')
                        ->label('Delivery details')
                        ->disabled()
                        ->columnSpanFull()
                        ->visible(fn(): bool => (bool) Auth::user()?->hasRole('super admin')),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('phone')
            ->columns([
                TextColumn::make('phone')->label('Phone')->searchable()->sortable(),
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(60)
                    ->searchable()
                    ->tooltip(fn($record) => $record->message),
                TextColumn::make('message_id')
                    ->label('Reference')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_blacklisted')
                    ->label('Blocked number')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success'),
                TextColumn::make('created_at')
                    ->label('Sent At')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    Action::make('view_raw_response')
                        ->label('Delivery details')
                        ->icon('heroicon-o-code-bracket')
                        ->color('info')
                        ->modalHeading('Delivery details from the SMS provider')
                        ->modalContent(fn($record) => view('filament.components.json-preview', [
                            'data' => $record->response,
                        ]))
                        ->modalSubmitAction(false)
                        ->visible(fn(): bool => (bool) Auth::user()?->hasRole('super admin')),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
