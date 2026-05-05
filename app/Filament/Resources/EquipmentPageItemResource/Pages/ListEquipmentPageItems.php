<?php

namespace App\Filament\Resources\EquipmentPageItemResource\Pages;

use App\Filament\Resources\EquipmentPageItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentPageItems extends ListRecords
{
    protected static string $resource = EquipmentPageItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
