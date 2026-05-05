<?php

namespace App\Http\Controllers;

use App\Models\EquipmentPageItem;
use App\Models\WorkCategory;
use App\Services\PageService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    public function __construct(
        private readonly PageService $pageService,
    ) {
    }

    public function home(): View
    {
        return view('pages.home', $this->pageData([
            'title' => $this->pageService->homeSeoTitle(),
            'description' => $this->pageService->homeSeoDescription(),
            'services' => $this->pageService->serviceItems(),
            'companyDescription' => $this->pageService->companyDescription()
                ?? 'Лазерная резка металла для производственных задач любой сложности: от единичных деталей до серийных заказов.',
        ]));
    }

    public function about(): RedirectResponse
    {
        return redirect()->route('equipment');
    }

    public function services(): RedirectResponse
    {
        return redirect()->route('equipment');
    }

    public function equipment(): View
    {
        return view('pages.equipment', $this->pageData([
            'title' => $this->pageService->equipmentSeoTitle() ?: 'Оборудование для лазерной резки металла в Оренбурге',
            'description' => $this->pageService->equipmentSeoDescription()
                ?: 'Парк оборудования для лазерной резки металла в Оренбурге: черновая сталь, нержавейка, алюминий и гравировка деталей по чертежам.',
            'equipmentItems' => $this->pageService->mapEquipmentItems(
                $this->pageService->equipmentItems()
            ),
        ]));
    }

    public function works(): View
    {
        return view('pages.works', $this->pageData([
            'title' => $this->pageService->worksSeoTitle() ?: 'Выполненные работы',
            'description' => $this->pageService->worksSeoDescription()
                ?: 'Категории выполненных работ по лазерной резке и изготовлению изделий из металла.',
            'categories' => $this->pageService->mapWorkCategories(
                $this->pageService->workCategories()
            ),
        ]));
    }

    public function workCategory(WorkCategory $category): View
    {
        return view('pages.work-category', $this->pageData([
            'title' => $category->meta_title ?: $category->name . ' | Выполненные работы',
            'description' => $category->meta_description ?: ($category->description ?: 'Подборка выполненных работ в выбранной категории.'),
            'category' => (object) [
                'name' => $category->name,
                'description' => $category->description,
                'image_url' => \App\Models\SiteSetting::resolveMediaPath($category->image, 'images/back.webp'),
            ],
            'works' => $this->pageService->mapWorks(
                $this->pageService->worksByCategory($category)
            ),
        ]));
    }

    public function contacts(): View
    {
        return view('pages.contacts', $this->pageData([
            'title' => $this->pageService->contactsSeoTitle() ?: 'Контакты',
            'description' => $this->pageService->contactsSeoDescription()
                ?: 'Контакты для быстрого расчёта стоимости лазерной резки металла.',
        ]));
    }

    public function privacy(): View|Factory
    {
        return view('pages.privacy', $this->pageData());
    }

    private function pageData(array $data = []): array
    {
        return [
            'siteSettings' => $this->pageService->siteSettings(),
            ...$data,
        ];
    }
}
