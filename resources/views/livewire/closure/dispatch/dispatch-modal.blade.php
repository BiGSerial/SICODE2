<div wire:ignore class="modal fade closure-modal" id="closure_dispatch_modal" tabindex="-1" aria-labelledby="closureDispatchModalLabel" aria-hidden="true"
    x-data="closureDispatchModal()" x-on:closure-dispatch-open.window="open($event.detail)">
    <style>
        .closure-modal .modal-dialog { max-width: min(860px, calc(100vw - 1.5rem)); }
        .closure-modal .modal-content { border: 0; border-radius: 10px; overflow: hidden; box-shadow: 0 24px 60px rgba(15, 23, 42, .28); position: relative; }
        .closure-modal__header { background: linear-gradient(120deg, #102033 0%, #155f67 70%, #0f8a77 100%); color: #f8fafc; padding: 1rem 1.25rem; }
        .closure-modal__title { font-size: 1.05rem; font-weight: 800; margin: 0; }
        .closure-modal__subtitle { color: rgba(248, 250, 252, .78); font-size: .8rem; margin-top: .15rem; }
        .closure-modal__body { background: #f8fafc; padding: 1rem 1.25rem; }
        .closure-modal__card { background: #fff; border: 1px solid #dbe3ef; border-radius: 8px; padding: .85rem; box-shadow: 0 8px 20px rgba(15, 23, 42, .05); }
        .closure-modal .form-label { color: #334155; font-size: .75rem; font-weight: 700; text-transform: uppercase; margin-bottom: .3rem; }
        .closure-modal__chip { align-items: center; background: #eef2f6; border-radius: 999px; color: #102033; display: inline-flex; font-size: .78rem; font-weight: 700; gap: .35rem; padding: .22rem .65rem; }
        .closure-modal__chip .dot { border-radius: 50%; color: #fff; display: inline-flex; font-size: .68rem; height: 18px; width: 18px; align-items: center; justify-content: center; }
        .closure-modal__list { border: 1px solid #dbe3ef; border-radius: 8px; max-height: 260px; overflow-y: auto; background: #fff; }
        .closure-modal__list table { margin: 0; table-layout: fixed; }
        .closure-modal__list thead th { background: #f1f5f9; color: #475569; font-size: .7rem; position: sticky; text-transform: uppercase; top: 0; z-index: 1; }
        .closure-modal__list td { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle; }
        .closure-modal__list tr.is-refused td { background: rgba(227, 44, 44, .06); }
        .closure-modal__processing { align-items: center; background: rgba(248, 250, 252, .9); color: #123f43; cursor: wait; display: none; flex-direction: column; font-weight: 700; gap: .75rem; inset: 0; justify-content: center; position: absolute; text-align: center; z-index: 20; }
        .closure-modal__footer { align-items: center; background: #fff; border-top: 1px solid #e2e8f0; display: flex; gap: .5rem; justify-content: flex-end; padding: .85rem 1.25rem; }
        .closure-modal__footer .btn { border-radius: 6px; font-weight: 700; min-height: 38px; min-width: 112px; }
        @media (max-width: 767.98px) { .closure-modal__footer { flex-direction: column-reverse; align-items: stretch; } }
        .closure-modal__skeleton { animation: closure-modal-shimmer 1.1s infinite; background: linear-gradient(90deg, #e8edf3 25%, #f6f8fa 50%, #e8edf3 75%); background-size: 200% 100%; border-radius: 4px; display: inline-block; height: 12px; width: 70%; }
        @keyframes closure-modal-shimmer { from { background-position: 200% 0; } to { background-position: -200% 0; } }
    </style>
    <script type="application/json" x-ref="directory">{!! json_encode($directory, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="closure-modal__processing" :style="processing ? 'display: flex' : ''" role="status" aria-live="polite">
                <span class="spinner-border" aria-hidden="true"></span>
                <div>Processando despacho de <span x-text="rows.length"></span> Ordem(ns)...<br><small class="text-muted">Não feche esta janela. Isso pode levar alguns instantes.</small></div>
            </div>

            <div class="closure-modal__header d-flex align-items-start justify-content-between gap-3">
                <div>
                    <h1 class="closure-modal__title" id="closureDispatchModalLabel" x-text="processed ? 'Resultado do despacho' : 'Despachar Encerramento'">Despachar Encerramento</h1>
                    <div class="closure-modal__subtitle" x-text="rows.length ? rows.length + ' Ordem(ns) selecionada(s)' : 'Nenhuma Ordem selecionada'"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" :disabled="processing" aria-label="Fechar"></button>
            </div>

            <div class="closure-modal__body">
                <template x-if="processed">
                    <div>
                        <div class="d-flex flex-wrap gap-2 mb-3" role="status">
                            <span class="closure-modal__chip" style="background: rgba(15,138,119,.14); color: #0a5c4e;"><i class="ri-checkbox-circle-line"></i> <span x-text="count('dispatched')"></span> despachada(s)</span>
                            <span class="closure-modal__chip" style="background: rgba(247,210,0,.22); color: #8a6d00;"><i class="ri-history-line"></i> <span x-text="count('already_dispatched')"></span> já despachada(s)</span>
                            <span class="closure-modal__chip" style="background: rgba(227,44,44,.12); color: #a11d1d;"><i class="ri-error-warning-line"></i> <span x-text="count('refused')"></span> recusada(s)</span>
                        </div>
                        <div class="closure-modal__list" style="max-height: 340px;">
                            <table class="table table-sm align-middle">
                                <colgroup><col style="width: 54px;"><col style="width: 130px;"><col style="width: 130px;"><col></colgroup>
                                <thead><tr><th class="ps-3">Tipo</th><th>Nota</th><th>Ordem</th><th>Resultado</th></tr></thead>
                                <tbody>
                                    <template x-for="r in receipt" :key="r.order_id">
                                        <tr :class="{ 'is-refused': r.status === 'refused' }">
                                            <td class="ps-3"><span class="badge" :class="badgeClass(r.type)" x-text="r.type || '—'"></span></td>
                                            <td x-text="r.note || '—'"></td>
                                            <td class="fw-semibold" x-text="r.ordem || r.order_id"></td>
                                            <td :title="r.message || ''">
                                                <span x-show="r.status === 'dispatched'" class="badge-stack badge-closed">Despachada</span>
                                                <span x-show="r.status === 'already_dispatched'" class="badge-stack badge-open">Já despachada</span>
                                                <span x-show="r.status === 'refused'"><span class="badge bg-danger">Recusada</span> <span class="text-muted small" x-text="r.message"></span></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="small text-muted mt-2" x-show="count('refused') > 0">As Ordens recusadas continuam selecionadas na lista para nova tentativa.</div>
                    </div>
                </template>

                <template x-if="!processed">
                    <div>
                        <div class="closure-modal__card mb-3">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <template x-for="t in typeChips" :key="t.letter">
                                    <span class="closure-modal__chip" :title="t.label">
                                        <span class="dot" :style="'background:' + t.color" x-text="t.letter"></span> <span x-text="t.count"></span>
                                    </span>
                                </template>
                                <span class="closure-modal__chip"><i class="ri-file-list-3-line"></i> <span x-text="noteCount"></span> Nota(s)</span>
                                <span class="closure-modal__chip" x-show="ineligible > 0" style="background: rgba(227,44,44,.12); color: #a11d1d;"><i class="ri-error-warning-line"></i> <span x-text="ineligible"></span> recusada(s)</span>
                            </div>
                            <div class="closure-modal__list">
                                <table class="table table-sm align-middle">
                                    <colgroup><col style="width: 54px;"><col style="width: 112px;"><col style="width: 112px;"><col style="width: 96px;"><col></colgroup>
                                    <thead><tr><th class="ps-3">Tipo</th><th>Nota</th><th>Ordem</th><th title="Mês de competência da meta">Compet.</th><th>Situação</th></tr></thead>
                                    <tbody>
                                        <template x-for="r in rows" :key="r.id">
                                            <tr :class="{ 'is-refused': r.eligible === false }">
                                                <td class="ps-3"><span class="badge" :class="badgeClass(r.type)" x-text="r.type || '—'"></span></td>
                                                <td x-text="r.note || '—'"></td>
                                                <td class="fw-semibold" x-text="r.ordem || r.id"></td>
                                                <td x-text="r.comp || '—'"></td>
                                                <td :title="r.reason || ''">
                                                    <span x-show="r.eligible === null" class="closure-modal__skeleton" aria-label="Verificando"></span>
                                                    <span x-show="r.eligible === true" class="text-success small"><i class="ri-check-line"></i> <span x-text="companyId ? 'Pronta para despacho' : 'Sem impedimentos até aqui'"></span></span>
                                                    <span x-show="r.eligible === false" class="text-danger small"><i class="ri-close-circle-line"></i> <span x-text="r.reason"></span></span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="closure-modal__card">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="closure-modal-company">Empresa de destino</label>
                                    <select id="closure-modal-company" class="form-select" x-model="companyId" :disabled="processing">
                                        <option value="">Selecione uma empresa</option>
                                        <template x-for="c in directory" :key="c.id"><option :value="c.id" x-text="c.name"></option></template>
                                    </select>
                                    <div class="form-text" x-show="suggestedId && companyId === suggestedId" id="closure-modal-company-suggested">Sugerida pela empresa do informe.</div>
                                    <div class="form-text text-warning-emphasis" x-show="companyHint" x-text="companyHint" id="closure-modal-company-hint"></div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="closure-modal-assignee">Usuário (opcional)</label>
                                    <select id="closure-modal-assignee" class="form-select" x-model="assigneeId" :disabled="!companyId || processing">
                                        <option value="">Enviar para a pilha da empresa</option>
                                        <template x-for="u in users" :key="u.id"><option :value="u.id" x-text="u.name"></option></template>
                                    </select>
                                </div>
                                <div class="col-12" x-show="ineligible > 0">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="closure-modal-only-eligible" x-model="onlyEligible">
                                        <label class="form-check-label" for="closure-modal-only-eligible">Despachar só as elegíveis <span class="text-muted small">(<span x-text="rows.length - ineligible"></span> de <span x-text="rows.length"></span>)</span></label>
                                    </div>
                                </div>
                            </div>
                            <div class="small text-muted mt-2" x-show="previewing"><span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Verificando elegibilidade...</div>
                        </div>

                        <div class="alert alert-warning mt-3 mb-0" role="alert" x-show="error" x-text="error"></div>
                    </div>
                </template>
            </div>

            <div class="closure-modal__footer">
                <button type="button" class="btn btn-primary" x-show="processed" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-outline-secondary" x-show="!processed" data-bs-dismiss="modal" :disabled="processing">Cancelar</button>
                <button type="button" id="closure-dispatch-confirm" class="btn text-white" style="background: var(--dash-green, #0f8a77);" x-show="!processed"
                    @click="confirm()" :disabled="!canConfirm" :title="previewReady ? '' : 'Aguarde a verificação de elegibilidade'">
                    <span x-show="!processing"><i class="ri-send-plane-line"></i> Confirmar despacho</span>
                    <span x-show="processing"><span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Despachando...</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        window.closureDispatchModal = window.closureDispatchModal || function () {
            const COLORS = { P: '#e32c2c', M: '#0f8a77', F: '#263cc8' };
            const LABELS = { P: 'Passivo', M: 'Meta atual', F: 'Meta futura' };

            return {
                directory: [], rows: [], companyId: '', assigneeId: '', onlyEligible: false,
                previewReady: false, previewing: false, processing: false, processed: false,
                receipt: [], error: null, seq: 0, timer: null, companyHint: null, suggestedId: null,

                init() {
                    try { this.directory = JSON.parse(this.$refs.directory.textContent || '[]'); } catch (e) { this.directory = []; }
                    // Fechar durante o processamento é bloqueado; ao fechar, o estado é zerado (nada vaza entre aberturas).
                    this.$el.addEventListener('hide.bs.modal', (e) => { if (this.processing) { e.preventDefault(); } });
                    this.$el.addEventListener('hidden.bs.modal', () => this.reset());
                    this.$watch('companyId', () => { this.assigneeId = ''; this.schedulePreview(); });
                    this.$watch('assigneeId', () => this.schedulePreview());
                },

                reset() {
                    clearTimeout(this.timer);
                    this.seq++;
                    Object.assign(this, { rows: [], companyId: '', assigneeId: '', onlyEligible: false, previewReady: false, previewing: false, processing: false, processed: false, receipt: [], error: null, companyHint: null, suggestedId: null });
                },

                // Abre na hora (sem rede): resumo vindo dos data-attributes das linhas; a prévia vem em segundo plano.
                open(detail) {
                    const rows = (detail && detail.rows) || [];
                    if (!rows.length) { return; }
                    this.reset();
                    this.rows = rows.map((r) => ({ id: Number(r.id), ordem: r.ordem ?? null, note: r.note ?? null, type: r.type || null, comp: r.comp || '—', company: r.company ? String(r.company) : null, eligible: null, reason: null }));
                    this.suggestCompany();
                    window.bootstrap.Modal.getOrCreateInstance(this.$el).show();
                    this.fetchPreview();
                },

                // Sugestão padrão: a empresa da relação com o informe, quando todas as Ordens selecionadas têm a mesma.
                suggestCompany() {
                    this.companyHint = null;
                    this.suggestedId = null;
                    const ids = [...new Set(this.rows.map((r) => r.company).filter(Boolean))];
                    const missing = this.rows.some((r) => !r.company);
                    if (!ids.length) { return; }
                    if (ids.length === 1 && !missing) {
                        if (this.directory.some((c) => String(c.id) === ids[0])) { this.suggestedId = ids[0]; this.companyId = ids[0]; }
                        else { this.companyHint = 'A empresa do informe não está habilitada no Encerramento. Escolha o destino manualmente.'; }
                        return;
                    }
                    this.companyHint = ids.length > 1
                        ? 'As Ordens selecionadas têm empresas de informe diferentes (' + ids.length + '). Escolha a empresa de destino manualmente.'
                        : 'Algumas Ordens não têm empresa de informe. Escolha a empresa de destino manualmente.';
                },

                schedulePreview() {
                    if (!this.rows.length || this.processed) { return; }
                    clearTimeout(this.timer);
                    this.seq++;
                    this.previewReady = false;
                    this.previewing = true;
                    this.rows = this.rows.map((r) => ({ ...r, eligible: null, reason: null }));
                    this.timer = setTimeout(() => this.fetchPreview(), 250);
                },

                async fetchPreview() {
                    const seq = ++this.seq;
                    this.previewReady = false;
                    this.previewing = true;
                    this.error = null;
                    try {
                        const res = await this.$wire.preview(this.rows.map((r) => r.id), this.companyId || null, this.assigneeId || null);
                        if (seq !== this.seq) { return; }
                        if (!res || res.error) { this.error = (res && res.error) || 'Não foi possível verificar a elegibilidade agora.'; return; }
                        this.mergeRows(res.rows);
                        this.previewReady = true;
                    } catch (e) {
                        if (seq === this.seq) { this.error = 'Não foi possível verificar a elegibilidade agora. Feche e tente novamente.'; }
                    } finally {
                        if (seq === this.seq) { this.previewing = false; }
                    }
                },

                mergeRows(serverRows) {
                    const byId = new Map((serverRows || []).map((r) => [Number(r.id), r]));
                    this.rows = this.rows.map((r) => {
                        const s = byId.get(r.id);
                        return s
                            ? { ...r, ordem: s.ordem, note: s.note, type: s.type, comp: s.comp, eligible: !!s.eligible, reason: s.reason }
                            : { ...r, eligible: false, reason: 'Ordem não encontrada.' };
                    });
                },

                async confirm() {
                    if (!this.canConfirm) { return; }
                    this.processing = true;
                    this.error = null;
                    try {
                        const res = await this.$wire.confirm(this.rows.map((r) => r.id), this.companyId, this.assigneeId || null, this.onlyEligible);
                        if (res && res.processed) {
                            this.receipt = res.receipt || [];
                            this.processed = true;
                        } else {
                            this.error = (res && res.error) || 'Não foi possível concluir o despacho agora.';
                            if (res && res.rows && res.rows.length) { this.mergeRows(res.rows); }
                        }
                    } catch (e) {
                        this.error = 'Não foi possível concluir o despacho agora. Confira a lista antes de tentar de novo.';
                    } finally {
                        this.processing = false;
                    }
                },

                count(status) { return this.receipt.filter((r) => r.status === status).length; },
                badgeClass(type) { return { P: 'bg-danger', M: 'bg-success', F: 'bg-primary' }[type] || 'bg-secondary'; },

                get users() {
                    const company = this.directory.find((c) => c.id === this.companyId);
                    return company ? company.users : [];
                },
                get ineligible() { return this.rows.filter((r) => r.eligible === false).length; },
                get noteCount() { return new Set(this.rows.map((r) => r.note || ('#' + r.id))).size; },
                get typeChips() {
                    return ['P', 'M', 'F'].map((letter) => ({ letter, label: LABELS[letter], color: COLORS[letter], count: this.rows.filter((r) => r.type === letter).length })).filter((t) => t.count > 0);
                },
                get canConfirm() {
                    return this.previewReady && !this.processing && !!this.companyId && this.rows.length > 0
                        && (this.ineligible === 0 || this.onlyEligible) && this.ineligible < this.rows.length;
                },
            };
        };
    </script>
</div>
