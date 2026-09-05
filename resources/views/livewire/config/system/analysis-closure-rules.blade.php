<div class="analysis-rules-page">
    <style>
        .analysis-rules-page .rules-card {
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .analysis-rules-page .rules-card__head {
            align-items: center;
            background: #123f43;
            color: #f8fafc;
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
            padding: 0.85rem 1rem;
        }

        .analysis-rules-page .rules-card__title {
            align-items: center;
            display: inline-flex;
            font-size: 1rem;
            font-weight: 800;
            gap: 0.45rem;
            margin: 0;
        }

        .analysis-rules-page .rules-card__body {
            padding: 1rem;
        }

        .analysis-rules-page .rules-field .form-label {
            color: #334155;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            margin-bottom: 0.35rem;
            text-transform: uppercase;
        }

        .analysis-rules-page .rules-field .form-control,
        .analysis-rules-page .rules-field .form-select {
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px;
        }

        .analysis-rules-page .rules-field .form-control:focus,
        .analysis-rules-page .rules-field .form-select:focus {
            border-color: #0f766e !important;
            box-shadow: 0 0 0 0.18rem rgba(15, 118, 110, 0.14);
        }

        .analysis-rules-page .rules-help {
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 600;
        }
    </style>

    <div class="rules-card">
        <div class="rules-card__head">
            <h4 class="rules-card__title">
                <i class="ri-settings-3-line"></i>
                Parâmetros de Encerramento - Análise
            </h4>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-light btn-sm" wire:click="resetToConfig">
                    <i class="ri-restart-line me-1"></i> Restaurar padrão
                </button>
                <button type="button" class="btn btn-light btn-sm text-dark" wire:click="save">
                    <i class="ri-save-3-line me-1"></i> Salvar
                </button>
            </div>
        </div>

        <div class="rules-card__body">
            <div class="row g-3">
                <div class="col-12 col-md-3 rules-field">
                    <label class="form-label">Regional</label>
                    <select class="form-select" wire:model="ruleset">
                        <option value="es">ES</option>
                        <option value="sp">SP</option>
                    </select>
                </div>

                <div class="col-12 col-md-4 rules-field">
                    <label class="form-label">Meio ambiente sem motivo?</label>
                    <select class="form-select" wire:model="environmentWithoutReason">
                        <option value="0">Não</option>
                        <option value="1">Sim</option>
                    </select>
                    <div class="rules-help mt-1">Quando ativo, Meio Ambiente não exige IEMA/ICMBIO.</div>
                </div>

                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        Informe uma opção por linha. Use <strong>valor|rótulo</strong>. Se não houver separador, o valor será usado como rótulo.
                    </div>
                </div>

                <div class="col-12 col-lg-6 rules-field">
                    <label class="form-label">Conclusões de Análise</label>
                    <textarea class="form-control" rows="12" wire:model.defer="analysisConclusions"></textarea>
                </div>

                <div class="col-12 col-lg-6 rules-field">
                    <label class="form-label">Conclusões de Pré-análise</label>
                    <textarea class="form-control" rows="12" wire:model.defer="preAnalysisConclusions"></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
