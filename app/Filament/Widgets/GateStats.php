<?php

namespace App\Filament\Widgets;

use App\Models\AccessLog;
use App\Models\RfidUser;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GateStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        return [
            Stat::make('Pengguna Terdaftar', RfidUser::count())->icon('heroicon-o-users'),
            Stat::make('Akses Diterima Hari Ini', AccessLog::whereDate('created_at', today())->where('status', 'granted')->count())->color('success'),
            Stat::make('Akses Ditolak Hari Ini', AccessLog::whereDate('created_at', today())->where('status', 'denied')->count())->color('danger'),
        ];
    }
}
