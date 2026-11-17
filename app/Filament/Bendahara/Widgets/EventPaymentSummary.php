<?php

namespace App\Filament\Bendahara\Widgets;

use App\Filament\Bendahara\Resources\EventResource;
use App\Models\Event;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class EventPaymentSummary extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->heading('Payments per event')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Event')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_visitors_count')
                    ->label('Registrations')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pending_count')
                    ->label('Pending')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pending_amount')
                    ->label('Pending amount')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((int) $state, 0, ',', '.'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('verified_amount')
                    ->label('Verified amount')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((int) $state, 0, ',', '.'))
                    ->sortable(),
            ])
            ->filters([
                Filter::make('has_payments')
                    ->label('Hide events without payments')
                    ->toggle()
                    ->default()
                    ->query(fn (Builder $query, array $data): Builder => ($data['isActive'] ?? false)
                        ? $query->whereHas('visitors', fn (Builder $visitors): Builder => $visitors->where('payment_amount', '>', 0))
                        : $query),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-right')
                    ->url(fn (Event $record): string => EventResource::getUrl('visitors', ['record' => $record->getKey()])),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;

        $inRange = fn (Builder $query): Builder => $query
            ->when($startDate, fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $endDate));

        $paid = fn (Builder $query): Builder => $inRange($query)->where('payment_amount', '>', 0);

        return Event::query()
            ->withCount([
                'visitors as paid_visitors_count' => $paid,
                'visitors as pending_count' => fn (Builder $query): Builder => $paid($query)->where('payment_verified', false),
            ])
            ->withSum(['visitors as pending_amount' => fn (Builder $query): Builder => $paid($query)->where('payment_verified', false)], 'payment_amount')
            ->withSum(['visitors as verified_amount' => fn (Builder $query): Builder => $paid($query)->where('payment_verified', true)], 'payment_amount');
    }
}
