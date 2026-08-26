<?php

namespace App\Services\WorkReports;

use App\Models\SystemSetting;
use Carbon\CarbonInterface;

class WorkReportAcceptanceSignature
{
    public const VERSION = 'ads_acceptance_2026_08_01_v1';

    public const STATEMENT_SETTING_KEY = 'work_report_acceptance_statement_text';

    public const CONTRACT_SETTING_KEY = 'work_report_acceptance_contract_text';

    public function statementText(): string
    {
        return SystemSetting::getValue(self::STATEMENT_SETTING_KEY, $this->defaultStatementText());
    }

    public function contractText(): string
    {
        return SystemSetting::getValue(self::CONTRACT_SETTING_KEY, $this->defaultContractText());
    }

    /**
     * Texto original, usado como valor de fábrica quando não há customização salva
     * e como opção de "restaurar padrão" na tela de administração.
     */
    public function defaultStatementText(): string
    {
        return 'Ao informar a obra no sistema, o usuário está em acordo que as informações passadas nesse Informe de Conclusão são verdadeiras e não existem divergências. Tendo ciência que existe um prazo para entrega da ADS conforme previsto em contrato, que a data do prazo será considerado o momento do envio deste informe, e não poderá ser contestado posteriormente. Você confirma o entendimento e ciência dessa informação?';
    }

    public function defaultContractText(): string
    {
        return 'A partir de 01/08/2026, aplica-se a base contratual ES.DT.PDN.02.01.006 - versão 06, item 5.3.4.d: para a EDP ES, a CONTRATADA dispõe do prazo de 3 (três) dias úteis, contados da conclusão da obra ou serviço, para a entrega do inventário; expirado esse prazo, prevalecerá o inventário elaborado pela CONTRATANTE. A adoção do inventário da CONTRATANTE não exime a CONTRATADA da obrigação de entrega, permanecendo a contagem do prazo até a regularização integral da pendência.';
    }

    public function signedText(): string
    {
        return $this->statementText() . "\n\n" . $this->contractText();
    }

    public function make(string $acceptedName, CarbonInterface $acceptedAt, array $context = []): array
    {
        $payload = [
            'version'     => self::VERSION,
            'signed_name' => trim($acceptedName),
            'signed_at'   => $acceptedAt->toIso8601String(),
            'signed_text' => $this->signedText(),
            'context'     => $context,
        ];

        return [
            'version'        => self::VERSION,
            'signed_name'    => $payload['signed_name'],
            'signed_at'      => $payload['signed_at'],
            'statement_text' => $this->statementText(),
            'contract_text'  => $this->contractText(),
            'signed_text'    => $payload['signed_text'],
            'hash_algorithm' => 'sha256',
            'hash_payload'   => $payload,
            'hash'           => hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
        ];
    }
}
