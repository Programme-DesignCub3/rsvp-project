<?php

namespace App\Filament\Bendahara\Resources\EventResource\Pages;

use App\Filament\Bendahara\Resources\EventResource;
use Filament\Resources\Pages\ListRecords;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
