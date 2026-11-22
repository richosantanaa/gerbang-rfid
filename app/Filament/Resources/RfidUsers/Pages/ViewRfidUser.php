<?php

namespace App\Filament\Resources\RfidUsers\Pages;

use App\Filament\Resources\RfidUsers\RfidUserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRfidUser extends ViewRecord
{
    protected static string $resource = RfidUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
