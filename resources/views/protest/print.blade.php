<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Laudo Técnico - Reclamação {{ $medProtest?->Protest?->nota }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        @page {
            size: A4;
            margin: 2cm 1cm 2cm 2cm;
        }

        html,
        body {
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #252525;
            background: #fff;
            margin: 0;
            padding: 0;
        }

        .report-container {
            max-width: 800px;
            margin: auto;
            padding: 0;
            background: #fff;
        }

        .report-header {
            border-bottom: 2px solid #1b3556;
            padding-bottom: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .report-header .logo {
            height: 60px;
        }

        .report-header .title-block {
            text-align: right;
        }

        .report-title {
            font-size: 1.9rem;
            font-weight: 700;
            color: #1b3556;
            margin-bottom: 2px;
        }

        .report-date {
            font-size: 1rem;
            color: #888;
        }

        .section-title {
            background: #f5f6fa;
            color: #263671;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 7px 0 6px 8px;
            border-left: 4px solid #1b3556;
            margin-top: 18px;
            margin-bottom: 9px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px 22px;
            margin-bottom: 7px;
        }

        .info-label {
            color: #5e6d89;
            font-size: 0.97rem;
        }

        .info-value {
            color: #262e3c;
            font-weight: 600;
            font-size: 1.02rem;
        }

        .details-block {
            margin-bottom: 12px;
        }

        .details-block strong {
            font-weight: 600;
        }

        .measures-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.96rem;
            margin-bottom: 20px;
        }

        .measures-table th,
        .measures-table td {
            border: 1px solid #c7d0e0;
            padding: 6px 8px;
            text-align: left;
        }

        .measures-table th {
            background: #ecf0fa;
            font-weight: 600;
            color: #23335a;
        }

        .evidence-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 8px 0 22px 0;
            min-height: 80px;
        }

        .evidence-img {
            width: 160px;
            height: 120px;
            object-fit: cover;
            border: 1px solid #ccd4e5;
            border-radius: 4px;
        }

        .evidence-placeholder {
            width: 160px;
            height: 120px;
            background: #f2f2f2;
            border: 1px dashed #bbb;
            color: #888;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            border-radius: 4px;
        }

        .signature-block {
            margin-top: 32px;
            display: flex;
            justify-content: space-between;
            gap: 36px;
        }

        .signature-field {
            flex: 1;
            text-align: center;
            margin: 0 12px;
        }

        .signature-line {
            border-bottom: 1.5px solid #444;
            width: 70%;
            margin: 38px auto 8px auto;
            height: 1px;
        }

        .signature-label {
            color: #888;
            font-size: 0.92rem;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body,
            html {
                background: #fff !important;
            }
        }
    </style>
</head>

<body>
    @if ($medProtest)
        <div class="report-container">

            <!-- Cabeçalho -->
            <div class="report-header">
                <img src="{{ asset('img/edp_documento.png') }}" alt="Logo da Empresa" class="logo">
                <div class="title-block">
                    <div class="report-title">LAUDO TÉCNICO</div>
                    <div class="report-date">Emitido em:
                        {{ $medProtest->completed_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>

            <!-- Identificação da Reclamação -->
            <div class="section-title">Identificação
                {{ $medProtest->Protest->tipoNota == 'OU' ? 'da Ouvidoria' : 'do Atendimento' }}</div>
            <div class="info-grid">
                <div>
                    <div class="info-label">Número:</div>
                    <div class="info-value">{{ $medProtest->Protest->nota }}#{{ $medProtest->med_id }}</div>
                </div>
                <div>
                    <div class="info-label">Tipo:</div>
                    <div class="info-value">{{ $medProtest->Protest->tipoNota }}</div>
                </div>
                <div>
                    <div class="info-label">Data de Abertura:</div>
                    <div class="info-value">{{ $medProtest->Protest->dtAberturaNota->format('d/m/Y') }}</div>

                </div>
                <div>
                    <div class="info-label">Município:</div>
                    <div class="info-value">{{ $medProtest->Protest->cidade }}</div>
                </div>
                <div>
                    <div class="info-label">Solicitante:</div>
                    <div class="info-value">EDP DISTRIBUIÇÃO</div>
                </div>
                <div>
                    <div class="info-label">Grupo Codificação:</div>
                    <div class="info-value">{{ $medProtest->Protest->codecodf }}</div>
                </div>
            </div>
            <div class="details-block">
                <div><strong>Causa:</strong> {{ $medProtest->txtCodCodificacao }}</div>
                <div><strong>Subcausa:</strong> {{ $medProtest->txtCodMedida }}.</div>
                <div><strong>Descrição:</strong>
                    {{ $medProtest->protest?->comments->last()?->message }}
                </div>
            </div>

            <!-- Medidas/Procedimentos -->
            <div class="section-title">Medidas e Procedimentos Executados</div>
            <table class="measures-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Status</th>
                        <th>Descrição</th>
                        <th>Data Início</th>
                        <th>Data Fim</th>
                        <th>Responsável</th>
                        {{-- <th>Observações</th> --}}
                    </tr>
                </thead>
                <tbody>
                    @if ($medProtest->Protest?->medProtests->count() > 0)
                        @foreach ($medProtest->Protest?->medProtests as $medida)
                            @php
                                $userResponsible = $medida->assignments?->where('user', true)->last()?->User?->name;
                            @endphp
                            <tr>
                                <td>#{{ $medida->med_id }}</td>
                                <td>{{ $medida->statusSist }}</td>
                                <td>{{ $medida->txtCodMedida }}</td>
                                <td>{{ $medida->dtCriacaoMedida->format('d/m/Y') }}</td>
                                <td>{{ $medida->dtFimMedida?->format('d/m/Y') }}</td>
                                <td>{{ $userResponsible ?? '---' }}</td>
                                {{-- <td>Poste realmente danificado.</td> --}}
                            </tr>
                        @endforeach

                    @endif

                </tbody>
            </table>

            <!-- Evidências e Anexos -->
            <div class="section-title">Evidências Fotográficas / Anexos</div>
            <div class="evidence-gallery">
                @if ($medProtest->EvidenceFiles->count() > 0)
                    @php
                        $imgExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg'];
                        $images = $medProtest->EvidenceFiles->filter(
                            fn($f) => in_array(strtolower($f->extension), $imgExt),
                        );
                    @endphp
                    @forelse ($images as $image)
                        <img src="{{ asset('storage/' . $image->path) }}" class="evidence-img"
                            alt="Evidência {{ $loop->iteration }}">

                    @empty
                        <div class="evidence-placeholder">Sem Evidências</div>
                    @endforelse

                @endif
                {{-- <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=400&q=80"
                    class="evidence-img" alt="Foto 1">
                <img src="https://images.unsplash.com/photo-1470770841072-f978cf4d019e?auto=format&fit=crop&w=400&q=80"
                    class="evidence-img" alt="Foto 2">
                <img src="https://images.unsplash.com/photo-1465101178521-c1a9136a0b16?auto=format&fit=crop&w=400&q=80"
                    class="evidence-img" alt="Foto 3"> --}}
            </div>

            <!-- Resumo técnico -->
            <div class="section-title">Resumo Técnico</div>
            <div class="details-block" style="min-height: 64px;">
                {{ $medProtest->TechnicalReport->content ?? 'Sem Resumo Técnico' }}
                <p> {{ $medProtest->TechnicalReport->created_at->format('d/m/Y H:i') ?? '' }}</p>
            </div>

            <!-- Assinaturas -->
            <div class="signature-block">
                <div class="signature-field">
                    <div class="signature-line"></div>
                    <div class="signature-label">{{ $medProtest->TechnicalReport->user->name ?? 'Sem Resumo Técnico' }}
                    </div>
                </div>
                <div class="signature-field">
                    <div class="signature-line"></div>
                    <div class="signature-label">Cliente / Solicitante</div>
                </div>
            </div>

            <div class="no-print" style="text-align: center; margin-top: 30px;">
                <button onclick="window.print()"
                    style="padding: 9px 28px; border: none; background: #1b3556; color: #fff; border-radius: 6px; font-weight: 600; font-size: 1.07rem; cursor: pointer;">
                    Imprimir / Salvar em PDF
                </button>
            </div>
        </div>
    @else
        <div class="report-container" style="text-align: center; padding: 50px;">
            <h1 style="color: #1b3556; font-size: 1.8rem; font-weight: 700;">Laudo Técnico não encontrado</h1>
            <p style="color: #5e6d89; font-size: 1.1rem; margin-top: 20px;">
                Não foi possível localizar o laudo técnico solicitado. Por favor, verifique o número da reclamação ou
                entre em contato com o suporte.
            </p>
        </div>
    @endif
</body>

</html>
