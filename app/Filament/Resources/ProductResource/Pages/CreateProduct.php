<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $this->getRecord()->syncCategoryPropertyValues($this->data['productProperties'] ?? []);
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
