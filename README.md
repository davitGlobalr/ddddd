# Books Booking (Laravel)

Приложение для бронирования книг с ролями пользователей, админ-панелью, событиями и очередями.

## О чем проект

- Каталог книг на главной странице (`/`, `/home`) с пагинацией.
- Клиент создает бронирование книги (`/booking`).
- Админ-страница бронирований (`/admin/bookings`) с поиском, пагинацией и изменением статуса.
- Ролевая модель на `spatie/laravel-permission`:
  - `superadmin`
  - `manager`
  - `customer`
- Права:
  - `booking.create`
  - `booking.updateStatus`

## Основные сущности

- `books`:
  - `name`, `author`, `description`, `quntity`, `img`, `price`
- `booking`:
  - `user_id`, `book_id`, `status`, timestamps

### Статусы booking

В проекте статусы хранятся строками:

- `1` - pending
- `2` - approved
- `3` - cancelled

## Архитектура процессов

### 1) Создание брони

Контроллер `BookingController@store`:

1. Валидирует `book_id` и `quantity`.
2. В транзакции:
   - блокирует книгу (`lockForUpdate`);
   - проверяет остаток;
   - проверяет, нет ли уже pending-брони у этого пользователя на эту книгу;
   - уменьшает `books.quntity`;
   - создает запись в `booking` со статусом `1`.
3. Диспатчит событие `ReservationCreated`.
4. Возвращает JSON (для AJAX) или redirect.

### 2) Event -> Listener -> Notification

- Event: `App\Events\ReservationCreated`
- Listener: `App\Listeners\SendReservationCreatedNotifications` (`ShouldQueue`)
- Notification: `App\Notifications\ReservationCreatedNotification`

При создании брони слушатель:

- отправляет email пользователю;
- отправляет email всем пользователям с ролями `superadmin` и `manager` (кроме автора брони).

Так как listener очередной (`ShouldQueue`), для отправки уведомлений должен работать worker.

### 3) Scheduler (авто-отмена pending)

- Команда: `booking:expire-pending`
- Класс: `App\Console\Commands\ExpirePendingBookings`
- Расписание: `routes/console.php` -> `everyMinute()`

Команда переводит старые pending-записи в cancelled.

## Быстрый запуск через Docker

### Требования

- Docker + Docker Compose

### 1. Подготовка окружения

```bash
cp .env.example .env
```

### 2. Запуск контейнеров

```bash
docker compose up -d --build
```

Сервисы из `docker-compose.yml`:

- `app` (php-fpm / Laravel)
- `nginx` (HTTP)
- `db` (MySQL 8)
- `redis`
- `phpmyadmin`
- `node` (Vite dev server)

### 3. Установка зависимостей и ключ приложения

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
```

### 4. Миграции и сиды

```bash
docker compose exec app php artisan migrate --seed
```

Сиды создают:

- роли и permissions (`RolePermissionSeeder`);
- 30 книг (`BooksSeeder`);
- тестового пользователя `test@example.com` (`DatabaseSeeder`).

### 5. Фронтенд

Если контейнер `node` не поднят:

```bash
docker compose run --rm node sh -lc "npm ci && npm run dev -- --host 0.0.0.0 --port 5173"
```

## URL и доступы

- Приложение: `http://localhost:8025`
- phpMyAdmin: `http://localhost:8013`
- MySQL порт на хосте: `3318`
- Redis порт на хосте: `16381`

## Как запускать очереди и шедулер

### Очередь (worker)

В `.env.example` используется `QUEUE_CONNECTION=database`, значит нужен воркер:

```bash
docker compose exec app php artisan queue:work --tries=1
```

Для разового прогона одного задания:

```bash
docker compose exec app php artisan queue:work --once --tries=1
```

После изменений кода listener/notification перезапускайте воркеры:

```bash
docker compose exec app php artisan queue:restart
```

### Scheduler

Локально обычно запускается отдельным процессом:

```bash
docker compose exec app php artisan schedule:work
```

Либо ручной запуск проверки расписания:

```bash
docker compose exec app php artisan schedule:run
```

## Как проверить, что все работает

### Проверка бронирования

1. Откройте главную страницу, создайте бронь.
2. Убедитесь, что запись появилась в таблице `booking`.
3. Убедитесь, что количество книги уменьшилось в `books.quntity`.

### Проверка event/listener/queue

1. Создайте бронь.
2. Проверьте очередь:
   ```bash
   docker compose exec app php artisan tinker --execute 'echo DB::table("jobs")->count();'
   ```
3. Запустите worker (`queue:work`).
4. Проверьте логи:
   ```bash
   docker compose exec app tail -n 200 storage/logs/laravel.log
   ```
   Ищите записи:
   - `booking.created`
   - `booking.reservation_created_event_dispatched`
   - `booking.reservation_created_listener_started`
   - `booking.reservation_created_listener_finished`

### Проверка scheduler

1. Создайте pending-бронирование.
2. Запустите:
   ```bash
   docker compose exec app php artisan booking:expire-pending
   ```
3. Проверьте, что статус поменялся на `3`.

## Полезные команды

```bash
docker compose ps
docker compose logs -f app
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan test --compact
```

## Примечания

- У проекта есть отдельные логи для отладки процесса бронирования и нотификаций.
- Если изменения в фронтенде не видны, проверьте что Vite/`node` сервис запущен.
