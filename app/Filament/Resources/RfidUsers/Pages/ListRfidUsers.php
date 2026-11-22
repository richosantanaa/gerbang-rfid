<?php

namespace App\Filament\Resources\RfidUsers\Pages;

use App\Filament\Resources\RfidUsers\RfidUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRfidUsers extends ListRecords
{
    protected static string $resource = RfidUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
