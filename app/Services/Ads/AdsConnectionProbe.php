<?php

namespace App\Services\Ads;

use Carbon\Carbon;
use Illuminate\Support\Facades\{Cache, DB, Log};
use Throwable;

/**
 * Teste de conexão do painel de ADS. O painel lê do banco local (espelho `ads_requests`) e, para a fila em tempo real, do SQL Server
 * (`sqlsrv2`). Com o SQL Server fora do ar cada tentativa trava ~30 s (login timeout padrão do driver), e a tela faz várias por
 * requisição, então a página "morria". Aqui o teste usa timeout curto e o resultado fica em cache: quem consome só toca no
 * `sqlsrv2` se o teste passou, e as partes sem conexão são desabilitadas sem derrubar as demais.
 */
class AdsConnectionProbe
{
    public const LOCAL = 'local';

    public const LIVE = 'sqlsrv2';

    /** Segundos de espera do login no teste do SQL Server. */
    private const LOGIN_TIMEOUT = 3;

    /** Segundos de espera do teste TCP na porta do SQL Server. */
    private const TCP_TIMEOUT = 2;

    /** Quanto tempo vale um resultado positivo / negativo (o negativo é curto para a volta ser percebida logo). */
    private const TTL_UP = 60;

    private const TTL_DOWN = 20;

    private const LABELS = [
        self::LOCAL => 'Banco local',
        self::LIVE  => 'SQL Server (fila em tempo real)',
    ];

    /**
     * @return array<string, array{label: string, up: bool, checked_at: Carbon, error: ?string}>
     */
    public function status(bool $fresh = false): array
    {
        return [
            self::LOCAL => $this->check(self::LOCAL, $fresh),
            self::LIVE  => $this->check(self::LIVE, $fresh),
        ];
    }

    public function up(string $connection): bool
    {
        return $this->check($connection)['up'];
    }

    /** Registra que uma consulta real falhou, para o resto da requisição (e das próximas) não insistir. */
    public function markDown(string $connection, Throwable $e): void
    {
        $this->store($connection, false, $this->shortError($e));
    }

    /** @return array{label: string, up: bool, checked_at: Carbon, error: ?string} */
    private function check(string $connection, bool $fresh = false): array
    {
        $key = $this->key($connection);

        if (!$fresh && ($cached = Cache::get($key)) !== null) {
            return $this->hydrate($connection, $cached);
        }

        try {
            $connection === self::LIVE ? $this->pingLive() : DB::select('select 1');

            return $this->hydrate($connection, $this->store($connection, true, null));
        } catch (Throwable $e) {
            Log::warning('Painel de ADS: conexão indisponível', ['connection' => $connection, 'error' => $this->shortError($e)]);

            return $this->hydrate($connection, $this->store($connection, false, $this->shortError($e)));
        }
    }

    /**
     * Primeiro um teste TCP (2 s): o driver ODBC, com o servidor fora do ar, ainda espera o login timeout e refaz a tentativa depois de 10 s
     * (reconexão resiliente), então só chamamos o driver quando a porta responde. Depois, uma conexão própria com login timeout curto,
     * para não herdar os ~30 s da conexão `sqlsrv2` normal.
     */
    private function pingLive(): void
    {
        $config = config('database.connections.' . self::LIVE);

        $socket = @fsockopen((string) ($config['host'] ?? ''), (int) ($config['port'] ?? 1433), $errno, $error, self::TCP_TIMEOUT);

        if (!$socket) {
            throw new \RuntimeException('Servidor não respondeu em ' . self::TCP_TIMEOUT . ' s.');
        }

        fclose($socket);

        config(['database.connections.sqlsrv2_probe' => array_merge($config, ['login_timeout' => self::LOGIN_TIMEOUT])]);

        DB::purge('sqlsrv2_probe');

        try {
            DB::connection('sqlsrv2_probe')->select('select 1 as ok');
        } finally {
            DB::purge('sqlsrv2_probe');
        }
    }

    /** @return array{up: bool, at: string, error: ?string} */
    private function store(string $connection, bool $up, ?string $error): array
    {
        $payload = ['up' => $up, 'at' => now()->toIso8601String(), 'error' => $error];

        Cache::put($this->key($connection), $payload, $up ? self::TTL_UP : self::TTL_DOWN);

        return $payload;
    }

    /**
     * @param array{up: bool, at: string, error: ?string} $payload
     *
     * @return array{label: string, up: bool, checked_at: Carbon, error: ?string}
     */
    private function hydrate(string $connection, array $payload): array
    {
        return [
            'label'      => self::LABELS[$connection] ?? $connection,
            'up'         => (bool) $payload['up'],
            'checked_at' => Carbon::parse($payload['at']),
            'error'      => $payload['error'],
        ];
    }

    private function key(string $connection): string
    {
        return 'ads-dashboard:connection:' . $connection;
    }

    private function shortError(Throwable $e): string
    {
        return mb_substr(preg_replace('/\s+/', ' ', strip_tags($e->getMessage())), 0, 180);
    }
}
