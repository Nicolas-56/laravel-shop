<?php

namespace Tests\Feature;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyFilterFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_stores_filter_flag(): void
    {
        $property = Property::query()->create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'select',
            'is_active' => true,
            'is_filter' => true,
        ]);

        $this->assertTrue($property->is_filter);
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'is_filter' => true,
        ]);
    }

    public function test_property_filter_flag_defaults_to_false(): void
    {
        $property = Property::query()->create([
            'name' => 'Weight',
            'slug' => 'weight',
            'type' => 'number',
            'is_active' => true,
        ]);

        $this->assertFalse($property->fresh()->is_filter);
    }
}
