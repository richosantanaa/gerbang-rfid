<?php

namespace App\Filament\Resources\RfidUsers\Pages;

use App\Filament\Resources\RfidUsers\RfidUserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRfidUser extends EditRecord
{
    protected static string $resource = RfidUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
