<?php

namespace App\Filament\Component;

use App\Enums\VisitorType;
use App\Mail\VisitorMail;
use App\Models\Event;
use App\Models\Visitor;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

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

    public static function paymentVerificationAction(): Action
    {
        return Action::make('payment_verification')
            ->label(fn (Visitor $record): string => $record->payment_verified ? 'Unverify' : 'Verify')
            ->icon(fn (Visitor $record): string => $record->payment_verified ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
            ->color(fn (Visitor $record): string => $record->payment_verified ? 'danger' : 'success')
            ->requiresConfirmation()
            ->modalHeading(fn (Visitor $record): string => $record->payment_verified ? 'Unverify payment?' : 'Verify payment?')
            ->modalDescription(fn (Visitor $record): string => $record->payment_verified
                ? 'This payment will be marked as not verified.'
                : 'Confirm the payment proof matches the amount and BERITA.')
            ->modalSubmitActionLabel(fn (Visitor $record): string => $record->payment_verified ? 'Unverify' : 'Verify')
            ->action(function (Visitor $record): void {
                $verified = ! $record->payment_verified;

                $record->update([
                    'payment_verified' => $verified,
                    'payment_verified_at' => $verified ? now() : null,
                    'payment_verified_by' => $verified ? auth()->id() : null,
                ]);
            });
    }

    public static function resendPaymentEmailAction(): Action
    {
        return Action::make('resend_payment_email')
            ->label('Resend email')
            ->icon('heroicon-o-envelope')
            ->color('gray')
            ->visible(fn (Visitor $record): bool => filled($record->email))
            ->requiresConfirmation()
            ->modalHeading('Resend email?')
            ->modalDescription(fn (Visitor $record): string => 'Send event and payment details to '.$record->email.'.')
            ->modalSubmitActionLabel('Send')
            ->action(function (Action $action, Visitor $record): void {
                try {
                    Mail::to($record->email)->send(new VisitorMail($record));
                } catch (Throwable $throwable) {
                    report($throwable);

                    Notification::make()
                        ->danger()
                        ->title('Failed to send email')
                        ->send();

                    $action->halt();
                }
            })
            ->successNotificationTitle('Email sent');
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
            TextColumn::make('business')
                ->searchable(),
            TextColumn::make('company')
                ->searchable(),
            TextColumn::make('email')
                ->searchable(),
            TextColumn::make('phone')
                ->searchable(),
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

            TextColumn::make('registration_sequence')
                ->label('Reg #')
                ->sortable()
                ->searchable()
                ->placeholder('-'),
            TextColumn::make('payment_amount')
                ->label('Payment')
                ->sortable()
                ->formatStateUsing(
                    fn ($state): string => $state === null
                        ? '-'
                        : ($state > 0 ? 'Rp '.number_format($state, 0, ',', '.') : 'Gratis')
                ),
            TextColumn::make('payment_berita')
                ->label('BERITA')
                ->placeholder('-'),
            IconColumn::make('payment_verified')
                ->label('Verified')
                ->boolean()
                ->visible(fn (): bool => in_array(Filament::getCurrentPanel()?->getId(), ['admin', 'bendahara'], true)),

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
