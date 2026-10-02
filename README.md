# BotGate for Laravel

**BotGate** — шлюз (прокси) для Telegram Bot API: приложение шлёт запросы в BotGate, а он проксирует их к Telegram с зарубежного сервера, благодаря чему боты стабильно работают из России без личных прокси и VPN. Единый API-ключ, шифрование токенов, webhook с повторами и статистика доступны в личном кабинете на [bot-gate.ru](https://bot-gate.ru/).

Этот пакет интегрирует [BotGate SDK](https://github.com/Alexby8/botgate-php-sdk) с Laravel: сервис-провайдер, фасад, готовый маршрут вебхука с проверкой подписи и событие для входящих обновлений Telegram.

Пакет не содержит бизнес-логики — он принимает и валидирует обновления, а дальше вы обрабатываете их в своих слушателях.

## Требования

- Laravel `^11.0 || ^12.0 || ^13.0`.
- PHP `^8.2` для Laravel 11/12; PHP `^8.3` для Laravel 13.

Поддержка Laravel 13 доступна начиная с `botgate/laravel` 1.1.0.

## Установка

```bash
composer require botgate/laravel
```

Сервис-провайдер и фасад регистрируются автоматически (package auto-discovery).

Composer подберёт совместимую версию пакета. PHP SDK `botgate/sdk`
установится автоматически как зависимость.

Опубликуйте конфигурацию:

```bash
php artisan vendor:publish --tag=botgate-config
```

## Настройка

Добавьте переменные в `.env`:

```dotenv
BOTGATE_API_KEY=ваш-api-ключ
BOTGATE_BASE_URL=https://bot-gate.ru
BOTGATE_WEBHOOK_SECRET=ваш-секрет-вебхука

# опционально
BOTGATE_TIMEOUT=30
BOTGATE_RETRY_MAX=3
BOTGATE_RETRY_BASE_DELAY_MS=500
BOTGATE_WEBHOOK_PATH=botgate/webhook
```

## Использование

Вызовы Bot API через фасад:

```php
use BotGate\Laravel\Facades\BotGate;

BotGate::bot('public-bot-id')->call('sendMessage', [
    'chat_id' => 123456789,
    'text' => 'Привет из Laravel!',
]);
```

Доступ к нижележащему SDK-клиенту и конфигурации:

```php
BotGate::client(); // BotGate\Client
BotGate::config(); // BotGate\Config
```

## Вебхук

Пакет регистрирует маршрут `POST` по пути из `botgate.webhook.path` (по умолчанию `/botgate/webhook`, имя маршрута `botgate.webhook`). На маршрут навешивается middleware `VerifyBotGateSignature`, который проверяет заголовок `X-BotGate-Signature` по секрету `BOTGATE_WEBHOOK_SECRET`. При неверной подписи возвращается `403`.

Укажите этот URL как адрес вебхука вашего бота в BotGate.

### Обработка обновлений

Каждое проверенное обновление публикуется как событие `BotGate\Laravel\Events\BotGateUpdateReceived`. Подпишитесь на него, например в `AppServiceProvider::boot()`:

```php
use BotGate\DTO\UpdateType;
use BotGate\Laravel\Events\BotGateUpdateReceived;
use Illuminate\Support\Facades\Event;

Event::listen(function (BotGateUpdateReceived $event): void {
    $update = $event->update; // BotGate\DTO\Update

    if ($update->is(UpdateType::Message)) {
        $message = $update->message();
        // ваша логика
    }
});
```

Либо создайте отдельный класс-слушатель в `app/Listeners` (Laravel найдёт его автоматически).

## Свой HTTP-клиент

Транспорт инвертирован: если в контейнере связан `BotGate\Http\HttpClientInterface`, клиент использует его, иначе берёт реализацию SDK по умолчанию (Guzzle + повторные попытки). Чтобы подменить транспорт, добавьте биндинг в своём сервис-провайдере:

```php
use BotGate\Http\HttpClientInterface;

$this->app->bind(HttpClientInterface::class, function () {
    return new MyHttpClient(/* ... */);
});
```

## Конфигурация

Все параметры находятся в `config/botgate.php` и читаются из `BOTGATE_*` переменных окружения. Маршрут, middleware и имя заголовка подписи также настраиваются в секции `webhook`.

## Лицензия

[MIT](LICENSE) © BotGate
