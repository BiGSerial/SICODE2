<?php

namespace App\Services\Partner;

use App\Models\SystemSetting;

class DashboardLegalNotices
{
    public const ADS_DUE_SETTING_KEY = 'partner_dashboard_ads_due_legal_note';

    public const ADS_OVERDUE_SETTING_KEY = 'partner_dashboard_ads_overdue_legal_note';

    public const VALUES_DISCLAIMER_SETTING_KEY = 'partner_dashboard_values_disclaimer';

    public function adsDueText(): string
    {
        return SystemSetting::getValue(self::ADS_DUE_SETTING_KEY, $this->defaultAdsDueText());
    }

    public function adsOverdueText(): string
    {
        return SystemSetting::getValue(self::ADS_OVERDUE_SETTING_KEY, $this->defaultAdsOverdueText());
    }

    public function valuesDisclaimerText(): string
    {
        return SystemSetting::getValue(self::VALUES_DISCLAIMER_SETTING_KEY, $this->defaultValuesDisclaimerText());
    }

    /**
     * Texto original, usado como valor de fábrica quando não há customização salva
     * e como opção de "restaurar padrão" na tela de administração.
     */
    public function defaultAdsDueText(): string
    {
        return '<p>A partir de 01/08/2026, aplica-se. <strong>ES.DT.PDN.02.01.006 - versão 06, item 5.3.4.d</strong>: para a EDP ES, a CONTRATADA dispõe do <strong>prazo de 3 (três) dias úteis</strong>, contados da conclusão da obra ou serviço, para a entrega do inventário; <strong>expirado esse prazo, prevalecerá o inventário elaborado pela CONTRATANTE</strong>.</p><p><strong>Observação: a adoção do inventário da CONTRATANTE não exime a CONTRATADA da obrigação de entrega, permanecendo a contagem do prazo até a regularização integral da pendência.</strong></p>';
    }

    public function defaultAdsOverdueText(): string
    {
        return '<p>A partir de 01/08/2026 aplica-se: <strong>ES.DT.PDN.00265, versão 06, item 5.8 (Penalidades)</strong>: estabelece as multas aplicáveis aos descumprimentos contratuais previstos no documento. Para o atraso na entrega dos documentos de inventário, aplica-se a penalidade específica prevista no <strong>item 5.8.9</strong>.</p>';
    }

    public function defaultValuesDisclaimerText(): string
    {
        return '<strong>Valores meramente informativos.</strong> Os valores apresentados correspondem exclusivamente às informações fornecidas pela parceira nas ADS dos informes e nos parciais solicitados válidos do período filtrado. <strong>Esses valores não constituem validação financeira, medição aprovada ou autorização para pagamento.</strong>';
    }
}
