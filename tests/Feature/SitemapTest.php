<?php

namespace Tests\Feature;

use App\Models\WorkCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_includes_static_pages_and_work_categories(): void
    {
        $publishedCategory = WorkCategory::query()->create([
            'name' => 'Александровы вазы',
            'slug' => 'aleksandrovy-vazy',
            'sort' => 1,
            'is_published' => true,
        ]);

        WorkCategory::query()->create([
            'name' => 'Черновик',
            'slug' => 'draft-category',
            'sort' => 2,
            'is_published' => false,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee(route('home'), false);
        $response->assertSee(route('equipment'), false);
        $response->assertSee(route('works'), false);
        $response->assertSee(route('contacts'), false);
        $response->assertSee(route('privacy'), false);
        $response->assertSee(route('works.category', 'aleksandrovy-vazy'), false);
        $response->assertDontSee(route('works.category', 'draft-category'), false);
        $response->assertSee('<lastmod>', false);
        $response->assertSee(
            '<lastmod>' . $publishedCategory->updated_at?->toDateString() . '</lastmod>',
            false
        );
    }
}
