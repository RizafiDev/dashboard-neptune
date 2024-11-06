<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Release;
use Filament\Support\Enums\IconPosition;

class AnalyticWidget extends BaseWidget
{

    protected function getStats(): array
    {
        return [
            // Stat::make('Total YouTube Streams', Release::sum('youtube_stream'))
            //     ->description('Total YouTube Streams')
            //     ->descriptionIcon('heroicon-m-musical-note', IconPosition::Before)
            //     ->chart(Release::pluck('youtube_stream')->toArray())  // Mengambil data dari kolom youtube_stream untuk grafik
            //     ->color('info')
        ];
    }
}