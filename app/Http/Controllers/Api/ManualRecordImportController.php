<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ManualRecordImportService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class ManualRecordImportController extends Controller
{
    public function store(Request $request, ManualRecordImportService $service)
    {
        $payload = $request->json()->all();
        $records = $this->recordsFromPayload($payload);

        if ($records === []) {
            return response()->json(['message' => 'Envie um registro JSON ou um array de registros.'], 422);
        }

        try {
            $summary = $service->import($records);
            $request->attributes->set('application_api_summary', $summary);

            return response()->json([
                'message' => 'Importação processada com sucesso.',
                'summary' => $summary,
            ], 200);
        } catch (InvalidArgumentException $exception) {
            $summary = [
                'records_received' => count($records),
                'error_message' => $exception->getMessage(),
            ];
            $request->attributes->set('application_api_summary', $summary);

            return response()->json([
                'message' => 'Payload inválido.',
                'error' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            $request->attributes->set('application_api_summary', [
                'records_received' => count($records),
                'error_message' => 'Falha interna ao processar a importação.',
            ]);

            report($exception);

            return response()->json([
                'message' => 'Falha interna ao processar a importação.',
            ], 500);
        }
    }

    private function recordsFromPayload(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        if (array_key_exists('data', $payload)) {
            if (!is_array($payload['data'])) {
                return [];
            }

            return array_is_list($payload['data'])
                ? $payload['data']
                : [$payload['data']];
        }

        return array_is_list($payload) ? $payload : [$payload];
    }
}
