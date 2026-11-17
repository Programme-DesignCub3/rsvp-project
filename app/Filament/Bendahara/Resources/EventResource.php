<?php

namespace App\Filament\Bendahara\Resources;

use App\Filament\Bendahara\Resources\EventResource\Pages;
use App\Filament\Component\EventTable;
use App\Models\Event;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Payment Verification';

    public static function table(Table $table): Table
    {
        return $table
            ->columns(EventTable::Event())
            ->defaultSort('start_date', 'desc')
            ->actions([
                Tables\Actions\Action::make('visitors')
                    ->label('Verify Payments')
                    ->icon('heroicon-o-user-group')
                    ->url(fn (Event $record): string => Pages\ManageVisitor::getUrl(['record' => $record->id])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'visitors' => Pages\ManageVisitor::route('/{record}/visitors'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
