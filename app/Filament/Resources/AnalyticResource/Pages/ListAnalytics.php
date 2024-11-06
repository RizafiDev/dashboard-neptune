<?php

namespace App\Filament\Resources\AnalyticResource\Pages;

use App\Filament\Resources\AnalyticResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Widgets\StreamWidget;

class ListAnalytics extends ListRecords
{
    protected static string $resource = AnalyticResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    // protected function getHeaderWidgets(): array
    // {
    //     return [
    //         StreamWidget::class
    //     ];
    // }

}
