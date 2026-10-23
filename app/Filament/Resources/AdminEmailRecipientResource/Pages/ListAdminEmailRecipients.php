<?php

namespace App\Filament\Resources\AdminEmailRecipientResource\Pages;

use App\Filament\Resources\AdminEmailRecipientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdminEmailRecipients extends ListRecords
{
    protected static string $resource = AdminEmailRecipientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
