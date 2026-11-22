<?php

namespace App\Filament\Resources\RfidUsers\Pages;

use App\Filament\Resources\RfidUsers\RfidUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRfidUser extends CreateRecord
{
    protected static string $resource = RfidUserResource::class;
}
