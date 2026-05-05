<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterFill(): void
    {
        $this->getRecord()->syncCategoryProperties();
        $this->form->fill([
            ...$this->data,
            'productProperties' => ProductResource::buildStandardPropertiesFormState($this->getRecord()),
        ]);
    }

    protected function afterSave(): void
    {
        $this->getRecord()->syncCategoryPropertyValues($this->data['productProperties'] ?? []);
        $this->form->fill([
            ...$this->data,
            'productProperties' => ProductResource::buildStandardPropertiesFormState($this->getRecord()->fresh()),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
