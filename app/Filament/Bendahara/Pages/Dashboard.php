<?php

namespace App\Filament\Bendahara\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('startDate')
                ->label('From')
                ->default(now()->startOfMonth()),
            DatePicker::make('endDate')
                ->label('To')
                ->default(now()->endOfMonth()),
        ]);
    }
}
