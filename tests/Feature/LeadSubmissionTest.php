<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_can_store_a_lead(): void
    {
        $this->withoutMiddleware();

        $service = Service::query()->create([
            'title' => 'Test Service',
            'slug' => 'test-service',
            'short_description' => 'Short text',
            'description' => 'Long text',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->post(route('leads.store'), [
            'name' => 'Ivan',
            'phone' => '+7 (999) 111-22-33',
            'email' => 'ivan@example.com',
            'service_id' => $service->id,
            'message' => 'Нужна помощь с запуском проекта.',
        ]);

        $response->assertRedirect(route('home'));

        $this->assertDatabaseHas('leads', [
            'name' => 'Ivan',
            'phone' => '+7 (999) 111-22-33',
            'email' => 'ivan@example.com',
            'service_id' => $service->id,
            'status' => 'new',
        ]);
    }
}
