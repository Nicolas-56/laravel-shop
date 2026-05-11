<?php

namespace Database\Seeders;

use App\Models\EquipmentPageItem;
use App\Models\Lead;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Work;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(OrderStatusSeeder::class);

        $services = [
            [
                'title' => 'Лазерная резка черновой стали',
                'slug' => 'mild-steel-cutting',
                'short_description' => 'Режем листовую сталь толщиной до 20 мм с повторяемой геометрией и чистой кромкой.',
                'description' => 'Берём в работу серийные и единичные заказы, подготавливаем детали к дальнейшей сварке, гибке и сборке.',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Лазерная резка нержавейки',
                'slug' => 'stainless-cutting',
                'short_description' => 'Изготавливаем детали из нержавеющего листа толщиной до 12 мм для промышленности и частных производств.',
                'description' => 'Контролируем точность и аккуратность реза, чтобы детали сразу уходили в дальнейшую обработку или монтаж.',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Гравировка металла',
                'slug' => 'metal-engraving',
                'short_description' => 'Наносим маркировку, шильды и технические обозначения на металлические поверхности.',
                'description' => 'Подготавливаем изделия для производства, складского учёта и брендирования без лишних операций.',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($services as $serviceData) {
            Service::query()->updateOrCreate(
                ['slug' => $serviceData['slug']],
                $serviceData
            );
        }

        $settings = [
            'site_name' => 'А7 Девелопмент',
            'site_logo' => 'images/logo.jpg',
            'hero_title' => 'Лазерная резка металла с промышленной точностью',
            'hero_subtitle' => 'Черновая сталь до 20 мм, нержавеющий лист до 12 мм и гравировка металла для производства, монтажа и серийных заказов.',
            'hero_background' => 'images/back.png',
            'company_phone' => '+7 (922) 854-74-86',
            'company_email' => 'pegov_92@mail.ru',
            'company_address' => 'Екатеринбург',
            'company_description' => 'Работаем с производственными и частными заказами: быстро рассчитываем стоимость, подготавливаем детали по чертежам и соблюдаем требования к точности, кромке и срокам.',
            'company_telegram' => 'https://t.me/laser_met96',
            'company_city' => 'Екатеринбург',
            'company_schedule' => 'Пн–Пт: 9:00–18:00',
            'company_business_name' => 'ИП Пегов Павел Сергеевич',
            'company_inn' => 'ИНН 667000000000',
            'company_ogrnip' => 'ОГРНИП 000000000000000',
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $equipmentItems = [
            [
                'title' => 'Лазерный комплекс для листового металла',
                'description' => 'Станок для раскроя листов с точным позиционированием и стабильным качеством кромки на серийных партиях.',
                'image' => 'images/back.png',
                'sort' => 1,
                'is_published' => true,
            ],
            [
                'title' => 'Участок обработки нержавеющего листа',
                'description' => 'Настроенный режим резки для нержавейки позволяет получать аккуратный рез и снижать объём последующей обработки.',
                'image' => 'images/back.png',
                'sort' => 2,
                'is_published' => true,
            ],
            [
                'title' => 'Зона маркировки и гравировки',
                'description' => 'Наносим шильды, технические надписи и маркировку на металлические детали и готовые изделия.',
                'image' => 'images/back.png',
                'sort' => 3,
                'is_published' => true,
            ],
        ];

        foreach ($equipmentItems as $equipmentItemData) {
            EquipmentPageItem::query()->updateOrCreate(
                ['title' => $equipmentItemData['title']],
                $equipmentItemData
            );
        }

        $works = [
            [
                'title' => 'Комплект монтажных пластин',
                'image' => 'images/back.png',
                'alt' => 'Монтажные пластины после лазерной резки',
                'sort' => 1,
                'is_published' => true,
            ],
            [
                'title' => 'Декоративные панели из нержавейки',
                'image' => 'images/back.png',
                'alt' => 'Декоративные панели из нержавеющей стали',
                'sort' => 2,
                'is_published' => true,
            ],
            [
                'title' => 'Техническая маркировка деталей',
                'image' => 'images/back.png',
                'alt' => 'Гравировка на металлических деталях',
                'sort' => 3,
                'is_published' => true,
            ],
        ];

        foreach ($works as $workData) {
            Work::query()->updateOrCreate(
                ['title' => $workData['title']],
                $workData
            );
        }

        User::query()->updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin',
            'is_admin' => true,
            'password' => Hash::make('password'),
        ]);

        $serviceIds = Service::query()->pluck('id');

        Lead::query()->updateOrCreate([
            'email' => 'olga@client.test',
        ], [
            'name' => 'Ольга Смирнова',
            'phone' => '+7 (999) 111-22-33',
            'service_id' => $serviceIds->first(),
            'message' => 'Нужен расчёт партии деталей из черновой стали.',
            'status' => 'new',
        ]);

        Lead::query()->updateOrCreate([
            'email' => 'igor@client.test',
        ], [
            'name' => 'Игорь Павлов',
            'phone' => '+7 (999) 444-55-66',
            'service_id' => $serviceIds->skip(1)->first(),
            'message' => 'Интересует резка нержавеющего листа по чертежу.',
            'status' => 'contacted',
        ]);
    }
}
