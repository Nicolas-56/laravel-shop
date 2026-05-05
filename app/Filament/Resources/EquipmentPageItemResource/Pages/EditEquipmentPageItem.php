<?php

namespace App\Filament\Resources\EquipmentPageItemResource\Pages;

use App\Filament\Resources\EquipmentPageItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentPageItem extends EditRecord
{
    protected static string $resource = EquipmentPageItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
