<?php

namespace App\Exports\Closure\Dashboard;

/** Paleta do Excel do Painel analítico: as mesmas cores da tela do Encerramento (tokens --dash-*) e dos sinais por cor. */
final class DashboardTheme
{
    public const INK   = '102033';
    public const MUTED = '64748B';
    public const LINE  = 'DBE4EA';
    public const SOFT  = 'F4F7F9';
    public const TEAL  = '155F67';
    public const GREEN = '0F8A77';
    public const BLUE  = '263CC8';
    public const RED   = 'E32C2C';
    public const AMBER = 'F7D200';

    /** Tom da LINHA por sinal (mesmos tons de table-primary/success/danger/warning usados na tela) e cor da borda lateral. */
    public const SIGNAL_ROW = ['assigned' => 'CFE2FF', 'done' => 'D1E7DD', 'overdue' => 'F8D7DA', 'pile' => 'FFF3CD'];

    public const SIGNAL_EDGE = ['assigned' => self::BLUE, 'done' => self::GREEN, 'overdue' => self::RED, 'pile' => self::AMBER];
}
