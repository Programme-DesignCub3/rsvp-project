<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->using(function (array $data): Model {
                    $eventDetail = $data['detail'] ?? $data['event_detail'] ?? [];

                    $eventData = [
                        'name' => $data['name'],
                        'start_date' => $data['start_date'],
                        'registration_date' => $data['registration_date'],
                        'registration_end' => $data['registration_end'] ?? null,
                        'session' => $data['session'] ?? null,
                        'checkable' => $data['checkable'] ?? false,
                        'checkable_one' => $data['checkable_one'] ?? false,
                        'hide' => $data['hide'] ?? false,
                        'coming_soon' => $data['coming_soon'] ?? false,
                    ];

                    $event = static::getModel()::create($eventData);

                    $eventDetailData = [
                        'enable_registration' => $eventDetail['enable_registration'] ?? true,
                        'online_link' => $eventDetail['online_link'] ?? null,
                        'online_password' => $eventDetail['online_password'] ?? null,
                        'online_time' => $eventDetail['online_time'] ?? null,
                        'offline_address' => $eventDetail['offline_address'] ?? null,
                        'offline_location' => $eventDetail['offline_location'] ?? null,
                        'offline_food_price' => $eventDetail['offline_food_price'] ?? null,
                        'offline_foods' => $eventDetail['offline_foods'] ?? null,
                        'show_invoice_upload' => $eventDetail['show_invoice_upload'] ?? false,
                        'excluded_payment_list' => $eventDetail['excluded_payment_list'] ?? null,
                        'registration_payment_prices' => $eventDetail['registration_payment_prices'] ?? null,
                        'offline_time' => $eventDetail['offline_time'] ?? null,
                        'override_offline_food_price_text' => $eventDetail['override_offline_food_price_text'] ?? false,
                        'offline_food_price_text' => $eventDetail['offline_food_price_text'] ?? null,
                        'food_required' => $eventDetail['food_required'] ?? false,
                        'food_type' => $eventDetail['food_type'] ?? null,
                        'override_deadline_text' => $eventDetail['override_deadline_text'] ?? false,
                        'deadline_text' => $eventDetail['deadline_text'] ?? null,
                        'event_type' => $eventDetail['event_type'] ?? null,
                        'override_online_visitor_type' => $eventDetail['override_online_visitor_type'] ?? false,
                        'online_visitor_type_list' => $eventDetail['online_visitor_type_list'] ?? null,
                        'override_offline_visitor_type' => $eventDetail['override_offline_visitor_type'] ?? false,
                        'offline_visitor_type_list' => $eventDetail['offline_visitor_type_list'] ?? null,
                        'override_what_to_prepare' => $eventDetail['override_what_to_prepare'] ?? false,
                        'what_to_prepare' => $eventDetail['what_to_prepare'] ?? null,
                        'override_description_1' => $eventDetail['override_description_1'] ?? false,
                        'description_1' => $eventDetail['description_1'] ?? null,
                        'override_description_2' => $eventDetail['override_description_2'] ?? false,
                        'description_2' => $eventDetail['description_2'] ?? null,
                        'override_video' => $eventDetail['override_video'] ?? false,
                    ];

                    $event->detail()->create($eventDetailData);

                    return $event;
                }),
        ];
    }
}
