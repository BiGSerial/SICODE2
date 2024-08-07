<div>
    <!-- Modal para Criar/Editar Usuário -->
    @if ($this->user)
        <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="userModalLabel">Criar/Editar Usuário</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="userForm">
                            <!-- Seção 1: Dados do Usuário -->
                            <div class="mb-3">
                                <h6 class="text-primary">Dados do Usuário</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="matricula" class="form-label">Matrícula</label>
                                        <input type="text" class="form-control" id="matricula" required>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label for="nome" class="form-label">Nome</label>
                                        <input type="text" class="form-control" id="nome" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="empresa" class="form-label">Empresa</label>
                                        <select class="form-select" id="empresa" required>
                                            <option value="">Selecione a Empresa</option>
                                            <!-- Options de empresas -->
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label for="contrato" class="form-label">Contrato</label>
                                        <select class="form-select" id="contrato" required>
                                            <option value="">Selecione o Contrato</option>
                                            <!-- Options de contratos -->
                                        </select>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-center">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" id="contratado">
                                            <label class="form-check-label" for="contratado">
                                                Contratado (Terceirizado)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção 2: Permissões de Usuário -->
                            <div class="mb-3">
                                <h6 class="text-primary">Permissões de Usuário</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="superAdmin">
                                            <label class="form-check-label" for="superAdmin">Super Admin</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="admin">
                                            <label class="form-check-label" for="admin">Admin</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="gerente">
                                            <label class="form-check-label" for="gerente">Gerente</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="engenheiro">
                                            <label class="form-check-label" for="engenheiro">Engenheiro</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="responsavel">
                                            <label class="form-check-label" for="responsavel">Responsável</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="operador">
                                            <label class="form-check-label" for="operador">Operador</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="empreiteira">
                                            <label class="form-check-label" for="empreiteira">Empreiteira</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção 3: Adicionar Serviços -->
                            <div class="mb-3">
                                <h6 class="text-primary">Adicionar Serviços</h6>
                                <div class="row">
                                    <div class="col-md-10">
                                        <select class="form-select" id="servicosDisponiveis">
                                            <option value="">Selecione um Serviço</option>
                                            <!-- Options de serviços -->
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-center">
                                        <button type="button" class="btn btn-success" id="addServico"><i
                                                class="ri-add-line"></i> Adicionar</button>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <ul class="list-group" id="listaServicos">
                                        <!-- Lista de serviços adicionados -->
                                    </ul>
                                </div>
                            </div>

                            <!-- Seção 4: Região de Controle -->
                            <div class="mb-3">
                                <h6 class="text-primary">Região de Controle</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="regiaoControle"
                                                id="norte">
                                            <label class="form-check-label" for="norte">Norte</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="regiaoControle"
                                                id="centroNorte">
                                            <label class="form-check-label" for="centroNorte">Centro Norte</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="regiaoControle"
                                                id="centroSul">
                                            <label class="form-check-label" for="centroSul">Centro Sul</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="regiaoControle"
                                                id="sul">
                                            <label class="form-check-label" for="sul">Sul</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="saveUser">Salvar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
