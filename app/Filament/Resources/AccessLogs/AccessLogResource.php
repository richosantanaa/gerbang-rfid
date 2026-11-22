<?php

namespace App\Filament\Resources\AccessLogs;

use App\Filament\Resources\AccessLogs\Pages\ListAccessLogs;
use App\Filament\Resources\RfidUsers\RfidUserResource;
use App\Models\AccessLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AccessLogResource extends Resource
{
    protected static ?string $model = AccessLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $modelLabel = 'Log Akses';

    protected static ?string $pluralModelLabel = 'Log Akses';

    protected static ?string $navigationLabel = 'Log Akses';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i:s')->sortable(),
                TextColumn::make('rfidUser.name')->label('Nama')->placeholder('Tidak dikenal'),
                TextColumn::make('uid')->label('UID')->fontFamily('mono')->searchable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => $state === 'granted' ? 'Diterima' : 'Ditolak')
                    ->color(fn ($state) => $state === 'granted' ? 'success' : 'danger'),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(['granted' => 'Diterima', 'denied' => 'Ditolak']),
            ])
            ->recordActions([
                Action::make('register')
                    ->label('Daftarkan')
                    ->icon('heroicon-o-user-plus')
                    ->visible(fn (AccessLog $record) => $record->rfid_user_id === null)
                    ->url(fn (AccessLog $record) => RfidUserResource::getUrl('create', ['uid' => $record->uid])),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('5s'); // auto-refresh tiap 5 detik
    }

    public static function getPages(): array
    {
        return ['index' => ListAccessLogs::route('/')];
    }
}
