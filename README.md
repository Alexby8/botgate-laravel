# BotGate for Laravel

Интеграция [BotGate SDK](https://bot-gate.ru/) с Laravel: сервис-провайдер, фасад, готовый маршрут вебхука с проверкой подписи и событие для входящих обновлений Telegram.

Пакет не содержит бизнес-логики — он принимает и валидирует обновления, а дальше вы обрабатываете их в своих слушателях.

## Требования

- PHP `^8.2`
- Laravel `11.x` или `12.x`

## Установка

```bash
composer require botgate/laravel
```

Сервис-провайдер и фасад регистрируются автоматически (package auto-discovery).

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
