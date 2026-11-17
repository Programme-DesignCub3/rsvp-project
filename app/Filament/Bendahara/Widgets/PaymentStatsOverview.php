<?php

namespace App\Filament\Bendahara\Widgets;

use App\Models\Visitor;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class PaymentStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;

        $inRange = fn (Builder $query): Builder => $query
            ->when($startDate, fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $endDate));

        $pending = $inRange(Visitor::query()
            ->where('payment_amount', '>', 0)
            ->where('payment_verified', false));
        $verified = $inRange(Visitor::query()
            ->where('payment_amount', '>', 0)
            ->where('payment_verified', true));

        return [
            Stat::make('Waiting verification', number_format((clone $pending)->count(), 0, ',', '.'))
                ->description('Payments not verified yet')
                ->color('warning')
                ->icon('heroicon-o-clock'),
            Stat::make('Pending amount', 'Rp '.number_format((int) (clone $pending)->sum('payment_amount'), 0, ',', '.'))
                ->color('warning')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Verified payments', number_format((clone $verified)->count(), 0, ',', '.'))
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            Stat::make('Verified amount', 'Rp '.number_format((int) (clone $verified)->sum('payment_amount'), 0, ',', '.'))
                ->color('success')
                ->icon('heroicon-o-check-badge'),
        ];
    }
}
