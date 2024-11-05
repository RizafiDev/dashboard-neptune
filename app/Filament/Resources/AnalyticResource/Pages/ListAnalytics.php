<?php

namespace App\Filament\Resources\AnalyticResource\Pages;

use App\Filament\Resources\AnalyticResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnalytics extends ListRecords
{
    protected static string $resource = AnalyticResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
