<?php

namespace App\Filament\Resources\ChannelWhitelistResource\Pages;

use App\Filament\Resources\ChannelWhitelistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChannelWhitelists extends ListRecords
{
    protected static string $resource = ChannelWhitelistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
