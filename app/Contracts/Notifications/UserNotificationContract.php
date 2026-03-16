<?php

namespace App\Contracts\Notifications;

interface UserNotificationContract
{
    public function title(): string;

    public function message(): string;

    public function link(): ?string;

    public function status(): int;

    public function extras(): array;

    public function actionType(): string;

    public function actionLabel(): string;

    public function actionIcon(): string;

    public function actionUrl(): ?string;

    public function toDatabase(): array;
}
