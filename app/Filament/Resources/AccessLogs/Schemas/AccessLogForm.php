<?php

namespace App\Filament\Resources\AccessLogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AccessLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('rfid_user_id')
                    ->relationship('rfidUser', 'name'),
                TextInput::make('uid')
                    ->required(),
                TextInput::make('status')
                    ->required(),
                TextInput::make('ip_address'),
            ]);
    }
}
