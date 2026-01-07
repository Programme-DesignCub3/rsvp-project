<?php

namespace App\Filament\Component;

use App\Enums\VisitorType;
use App\Models\Event;
use App\Models\Visitor;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\View\View;

class EventTable
{
    public static function Event()
    {
        return [
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            TextColumn::make('start_date')
                ->searchable()
                ->sortable(),
            TextColumn::make('registration_date')
                ->searchable()
                ->sortable(),
            TextColumn::make('url')
                ->label('Registration URL')
                ->color(
                    function ($state) {
                        return $state === '#' ? 'gray' : 'success';
                    }
                )
                ->formatStateUsing(
                    fn ($state) => $state === '#' ? 'No URL' : $state.' (Click to copy)'
                )
                ->copyable(
                    fn (Event $record) => $record->detail !== null ? true : false
                )
                ->copyMessage('URL address copied')
                ->copyMessageDuration(1500),
        ];
    }

    public static function ManageVisitor()
    {
        return [
            TextColumn::make('index')
                ->label('#')
                ->rowIndex(),
            TextColumn::make('type')
                ->placeholder('Type')
                ->sortable()
                ->searchable()
                ->formatStateUsing(
                    fn ($state): string => in_array($state, array_column(VisitorType::cases(), 'value'))
                        ? VisitorType::from($state)->getLabel()
                        : (! empty($state) ? $state : 'Unknown')
                ),
            TextColumn::make('name')
                ->sortable()
                ->searchable(),
            TextColumn::make('created_at')
                ->label('Register Date')
                ->sortable()
                ->formatStateUsing(fn ($state): string => (new \DateTime($state))->format('d F Y')),
            TextColumn::make('register_time')
                ->label('Register Time')
                // ->sortable()
                ->state(fn (Visitor $visitor): string => (new \DateTime($visitor->created_at))->format('H:i')),
            TextColumn::make('business'),
            TextColumn::make('company'),
            TextColumn::make('email'),
            TextColumn::make('phone'),
            TextColumn::make('invited_by'),

            IconColumn::make('is_online')
                ->label('Online Presence')
                ->icon(fn (string $state): string => match ($state) {
                    '1' => 'heroicon-o-check-circle',
                    '0' => 'heroicon-o-x-circle',
                    default => 'heroicon-o-x-circle',
                }),
            IconColumn::make('is_offline')
                ->label('Offline Presence')
                ->icon(fn (string $state): string => match ($state) {
                    '1' => 'heroicon-o-check-circle',
                    '0' => 'heroicon-o-x-circle',
                    default => 'heroicon-o-x-circle',
                }),
            TextColumn::make('status'),
            TextColumn::make('meta')
                // ->listWithLineBreaks()
                // ->bulleted()
                ->getStateUsing(function ($record) {
                    $meta = [];
                    if ($record->meta) {
                        foreach ($record->meta as $key => $value) {
                            if (! in_array($key, ['offline_food', 'payment_path'])) {
                                $meta[] = ucfirst(str_replace('_', ' ', $key)).": $value";
                            }
                        }
                    }

                    return implode(";\n", $meta);
                })
                ->listWithLineBreaks()
                ->separator(';')
                ->bulleted()
                ->label('extra info'),

            TextColumn::make('food')
                ->listWithLineBreaks()
                ->bulleted()
                ->label('Packaged Food'),

            SpatieMediaLibraryImageColumn::make('Payment Proof')
                ->collection('payment_proof')
                ->checkFileExistence(false)
                ->action(
                    Action::make('show_payment_proof')
                        ->label('Payment Proof')
                        // ->action(fn (Visitor $visitor) => $visitor->payment_path_url)
                        ->modalContent(
                            fn (Visitor $visitor): View => view(
                                'filament.resources.event-resource.pages.image-modal',
                                ['image' => $visitor->getFirstMediaUrl('payment_proof')]
                            )
                        )
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalWidth(MaxWidth::ScreenMedium)
                )
                ->label('Payment Proof'),
        ];
    }
}
