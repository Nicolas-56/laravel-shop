# Laravel + Filament MVP

Минимальный MVP корпоративного сайта услуг на Laravel и Filament с запуском через Docker Compose.

## Что внутри

- Laravel application
- Filament admin panel по адресу `/admin`
- Blade-фронтенд со страницами `/`, `/about`, `/services`, `/contacts`
- модели `Service`, `Lead`, `SiteSetting`
- форма заявки на главной странице
- тестовые сиды и администратор `admin@example.com / password`

## Запуск

```bash
docker compose up --build
```

После первого запуска доступны:

- сайт: `http://localhost:8080`
- админка: `http://localhost:8080/admin`
- MySQL: `127.0.0.1:33067`

## Полезные команды

Установка и запуск в фоне:

```bash
docker compose up --build -d
```

Остановка:

```bash
docker compose down
```

Остановка с удалением тома MySQL:

```bash
docker compose down -v
```

Ручной запуск artisan внутри контейнера:

```bash
docker compose exec php php artisan migrate
docker compose exec php php artisan db:seed
docker compose exec php php artisan test
```

## Как работает первый старт

Контейнер `php` при старте:

1. ждёт готовности MySQL
2. выполняет `composer install`
3. ставит npm-зависимости и собирает Vite-ассеты
4. генерирует `APP_KEY`
5. запускает миграции и сиды
6. поднимает `php-fpm`

Это сделано специально, чтобы проект можно было поднять локально одной командой без дополнительных ручных шагов.
