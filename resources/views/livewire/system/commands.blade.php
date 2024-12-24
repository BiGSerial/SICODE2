<div>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Gerenciador de Comandos Artisan</h4>
                    </div>
                    <div class="card-body">
                        <form id="artisanForm" onsubmit="executeCommand(event)">
                            <div class="mb-4">
                                <label for="command" class="form-label">Comando Artisan</label>
                                <div class="input-group">
                                    <span class="input-group-text">php artisan</span>
                                    <input type="text" class="form-control" id="command" name="command"
                                        placeholder="Digite seu comando">
                                </div>
                            </div>

                            <div class="mb-4">
                                <button type="submit" class="btn btn-primary" id="executeBtn">
                                    Executar Comando
                                </button>
                            </div>

                            <!-- Status do Comando -->
                            <div id="commandStatus" class="alert d-none">
                                <!-- Status será inserido aqui -->
                            </div>

                            <!-- Output do Comando -->
                            <div id="commandOutput" class="mt-4 d-none">
                                <h5>Resultado do Comando:</h5>
                                <pre class="bg-dark text-light p-3 rounded"><code id="outputText"></code></pre>
                            </div>

                            <div class="d-grid gap-2">
                                <h5 class="mb-3">Comandos Rápidos</h5>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="setCommand('sicode:chk_integridade')">
                                    Atualizar Base OV
                                </button>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="setCommand('sicode:upd_baseEP')">
                                    Atualizar Base EP
                                </button>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="setCommand('sicode:upd_baseOrder')">
                                    Atualizar Ordens
                                </button>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="setCommand('sicode:upd_baseOperation')">
                                    Atualizar Operaçoes
                                </button>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="setCommand('sicode:operation-resp-upd')">
                                    Atualizar Operações responsáveis
                                </button>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function setCommand(command) {
            document.getElementById('command').value = command;
        }

        function showStatus(message, type = 'info') {
            const statusDiv = document.getElementById('commandStatus');
            statusDiv.className = `alert alert-${type}`;
            statusDiv.textContent = message;
            statusDiv.classList.remove('d-none');
        }

        function showOutput(output) {
            const outputDiv = document.getElementById('commandOutput');
            const outputText = document.getElementById('outputText');
            outputText.textContent = output;
            outputDiv.classList.remove('d-none');
        }

        async function executeCommand(event) {

            event.preventDefault();




            const command = document.getElementById('command').value;
            const executeBtn = document.getElementById('executeBtn');


            console.log(JSON.stringify({
                command
            }));


            if (!command) {
                showStatus('Por favor, digite um comando', 'warning');
                return;
            }

            // Desabilita o botão durante a execução
            executeBtn.disabled = true;

            try {
                showStatus('Iniciando execução do comando...', 'info');

                const response = await fetch('/system/commands/execute', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    body: JSON.stringify({
                        command
                    })
                });

                const data = await response.json();

                if (data.status === 'started') {
                    showStatus(data.message, 'info');
                    console.log(data.pid);

                    // pollCommandStatus(data.pid);
                } else {
                    showStatus('Erro ao iniciar comando ' + data.error, 'danger');
                    executeBtn.disabled = false;
                }
            } catch (error) {

                showStatus('Erro ao executar comando: ' + error, 'danger');
                executeBtn.disabled = false;



            }
        }

        async function pollCommandStatus(pid) {
            try {
                const response = await fetch(`/system/commands/status/${pid}`);
                const data = await response.json();



                if (data.status === 'completed') {
                    showStatus('Comando executado com sucesso!', 'success');
                    console.log(data.details);
                    if (data.output) {
                        showOutput(data.output);
                    }
                    document.getElementById('executeBtn').disabled = false;
                    return;
                }

                if (data.status === 'running') {
                    showStatus(data.message, 'info');

                    setTimeout(() => pollCommandStatus(pid), 2000);
                } else {
                    showStatus('Status desconhecido do comando', 'warning');
                    document.getElementById('executeBtn').disabled = false;
                }
            } catch (error) {
                showStatus('Erro ao verificar status: ' + error, 'danger');

                console.log('Mensagem:', error.message);
                console.log('Stack trace:', error.stack);
                console.log('Tipo de erro:', error.name);

                document.getElementById('executeBtn').disabled = false;
            }
        }
    </script>
</div>
