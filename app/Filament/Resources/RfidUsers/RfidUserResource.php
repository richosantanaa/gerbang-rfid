<?php

namespace App\Filament\Resources\RfidUsers;

use App\Filament\Resources\RfidUsers\Pages\CreateRfidUser;
use App\Filament\Resources\RfidUsers\Pages\EditRfidUser;
use App\Filament\Resources\RfidUsers\Pages\ListRfidUsers;
use App\Models\RfidUser;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RfidUserResource extends Resource
{
    protected static ?string $model = RfidUser::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $modelLabel = 'Pengguna RFID';

    protected static ?string $pluralModelLabel = 'Pengguna RFID';

    protected static ?string $navigationLabel = 'Pengguna RFID';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Pengguna')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Nama')->required()->maxLength(100),
                    TextInput::make('position')->label('Jabatan')->maxLength(100),
                    TextInput::make('uid')
                        ->label('UID Kartu RFID')
                        ->placeholder('92 FD 36 B9')
                        ->helperText('Format bebas, otomatis diseragamkan. Lihat UID di Serial Monitor atau menu Log Akses.')
                        ->default(fn () => request()->query('uid'))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn ($state) => RfidUser::normalizeUid($state)),
                    Toggle::make('is_active')->label('Aktif')->default(true)->inline(false),
                    Textarea::make('notes')->label('Catatan')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('position')->label('Jabatan')->badge()->searchable(),
                TextColumn::make('uid')->label('UID')->fontFamily('mono')->copyable()->searchable(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('created_at')->label('Terdaftar')->dateTime('d M Y')->sortable(),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Status Aktif')])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRfidUsers::route('/'),
            'create' => CreateRfidUser::route('/create'),
            'edit' => EditRfidUser::route('/{record}/edit'),
        ];
    }
}
