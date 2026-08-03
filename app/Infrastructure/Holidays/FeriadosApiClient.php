<?php

namespace App\Infrastructure\Holidays;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FeriadosApiClient
{
    private function client(): PendingRequest
    {
        $baseUrl = rtrim((string) config('services.feriados_api.url'), '/');
        $token = (string) config('services.feriados_api.key');

        if ($token === '') {
            throw new RuntimeException('A chave FERIADOS_API_KEY não foi configurada.');
        }

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->withToken($token)
            ->timeout(15)
            ->retry(2, 500);
    }

    public function holidaysByState(string $state, int $year): array
    {
        $state = strtoupper(trim($state));

        if (!preg_match('/^[A-Z]{2}$/', $state)) {
            throw new RuntimeException('UF inválida.');
        }

        if ($year < 2000 || $year > 2100) {
            throw new RuntimeException('Ano inválido.');
        }

        $response = $this->client()
            ->get("/api/v1/feriados/estado/{$state}", [
                'ano' => $year,
                'limit' => 100,
            ])
            ->throw()
            ->json();

        if (!is_array($response)) {
            throw new RuntimeException('Resposta inválida da Feriados API.');
        }

        return $response;
    }
}
