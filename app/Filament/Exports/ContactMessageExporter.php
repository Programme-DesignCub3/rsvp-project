<?php

namespace App\Filament\Exports;

use App\Models\ContactMessage;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;

class ContactMessageExporter extends Exporter
{
    protected static ?string $model = ContactMessage::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('created_at')
                ->label('Received At'),
            ExportColumn::make('fullname')
                ->label('Full Name'),
            ExportColumn::make('business_classification')
                ->label('Business Classification'),
            ExportColumn::make('company_name')
                ->label('Company Name'),
            ExportColumn::make('email'),
            ExportColumn::make('phone'),
            ExportColumn::make('message'),
            ExportColumn::make('is_read')
                ->label('Read')
                ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            DatePicker::make('from')
                ->label('Received from')
                ->helperText('Optional')
                ->rules(['date']),
            DatePicker::make('until')
                ->label('Received until')
                ->helperText('Optional')
                ->rules(['date']),
            Select::make('is_read')
                ->label('Read status')
                ->placeholder('All messages')
                ->options([
                    '0' => 'Unread',
                    '1' => 'Read',
                ]),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your contact message export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
