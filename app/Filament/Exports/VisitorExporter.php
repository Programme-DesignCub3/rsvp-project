<?php

namespace App\Filament\Exports;

use App\Enums\VisitorType;
use App\Models\Visitor;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;

class VisitorExporter extends Exporter
{
    protected static ?string $model = Visitor::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('created_at')
                ->label('Register Date')
                ->formatStateUsing(fn ($state): string => (new \DateTime($state))->format('d F Y')),
            ExportColumn::make('name'),
            ExportColumn::make('type')
                ->formatStateUsing(
                    fn ($state): string => in_array($state, array_column(VisitorType::cases(), 'value'))
                        ? VisitorType::from($state)->getLabel()
                        : 'Unknown'
                ),
            ExportColumn::make('business'),
            ExportColumn::make('company'),
            ExportColumn::make('phone'),
            ExportColumn::make('email'),
            ExportColumn::make('invited_by'),
            ExportColumn::make('food'),
            ExportColumn::make('registration_sequence')
                ->label('Registration No'),
            ExportColumn::make('payment_base_amount')
                ->label('Payment Base Amount'),
            ExportColumn::make('payment_amount')
                ->label('Payment Amount'),
            ExportColumn::make('payment_berita')
                ->label('BERITA'),
            ExportColumn::make('payment_verified')
                ->label('Payment Verified'),
            ExportColumn::make('payment')
                ->enabledByDefault(fn (): bool => Filament::getCurrentPanel()->getId() === 'admin'),
            ExportColumn::make('Session')
                ->state(function (Visitor $record): string {
                    return match (true) {
                        $record->is_online && $record->is_offline => 'Online & Offline',
                        $record->is_online => 'Online',
                        $record->is_offline => 'Offline',
                        default => '',
                    };
                }),
            ExportColumn::make('meta')
                ->getStateUsing(function ($record) {
                    $meta = [];
                    if ($record->meta) {
                        foreach ($record->meta as $key => $value) {
                            if (! in_array($key, ['offline_food', 'payment_path'])) {
                                $meta[] = ucfirst(str_replace('_', ' ', $key)).": $value";
                            }
                        }
                    }

                    return implode("\n", $meta);
                })
                ->label('extra info'),
            ExportColumn::make('status'),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('visitorType')
                ->options(visitorType::class)
                ->multiple()
                ->label('Select Visitor Type to export'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your visitor export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
