<?php

namespace Tests\Unit;

use App\Notifications\Channels\IdempotentDatabaseChannel;
use App\Notifications\SystemNotification;
use App\Support\Notifications\UserNotificationData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use PDOException;
use PHPUnit\Framework\TestCase;

class IdempotentDatabaseChannelTest extends TestCase
{
    public function test_returns_existing_notification_when_database_insert_was_already_committed(): void
    {
        $existingNotification = new class () extends Model {
            protected $guarded = [];
        };
        $existingNotification->forceFill(['id' => '3694f53f-3093-4829-9c49-0e9dcf9d65fe']);

        $route = new class ($existingNotification) {
            public string $lookupKey = '';

            public function __construct(private Model $existingNotification)
            {
            }

            public function create(array $payload): Model
            {
                throw new QueryException(
                    'mysql',
                    'insert into notifications',
                    [],
                    new PDOException(
                        "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '{$payload['id']}' for key 'PRIMARY'",
                        23000
                    )
                );
            }

            public function whereKey(string $key): self
            {
                $this->lookupKey = $key;

                return $this;
            }

            public function first(): Model
            {
                return $this->existingNotification;
            }
        };

        $notifiable = new class ($route) {
            public function __construct(private object $route)
            {
            }

            public function routeNotificationFor(string $driver, object $notification): object
            {
                return $this->route;
            }
        };

        $notification = new SystemNotification(new UserNotificationData('ADS disponível', 'Mensagem'));
        $notification->id = '3694f53f-3093-4829-9c49-0e9dcf9d65fe';

        $result = (new IdempotentDatabaseChannel())->send($notifiable, $notification);

        $this->assertSame($existingNotification, $result);
        $this->assertSame($notification->id, $route->lookupKey);
    }
}
