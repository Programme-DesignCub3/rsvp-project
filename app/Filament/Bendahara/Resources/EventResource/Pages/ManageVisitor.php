<?php

namespace App\Filament\Bendahara\Resources\EventResource\Pages;

use App\Enums\VisitorType;
use App\Filament\Bendahara\Resources\EventResource;
use App\Filament\Component\EventTable;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ManageVisitor extends ManageRelatedRecords
{
    protected static string $resource = EventResource::class;

    protected static string $relationship = 'visitors';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $title = 'Visitors';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns(EventTable::ManageVisitor())
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->multiple()
                    ->options(VisitorType::class),
                TernaryFilter::make('payment_verified')
                    ->label('Payment Verified')
                    ->trueLabel('Verified')
                    ->falseLabel('Not verified'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                EventTable::paymentVerificationAction(),
                EventTable::resendPaymentEmailAction(),
            ]);
    }
}
