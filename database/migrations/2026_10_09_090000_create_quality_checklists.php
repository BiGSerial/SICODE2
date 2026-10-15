<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('quality_checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version', 30);
            $table->boolean('active')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['name', 'version']);
        });
        Schema::create('quality_checklist_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_checklist_template_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('quality_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_checklist_section_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->text('question');
            $table->string('severity_sp', 16);
            $table->string('severity_qa', 16);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['quality_checklist_section_id', 'code']);
        });
        Schema::create('quality_checklist_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_process_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quality_stage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quality_checklist_template_id')->constrained();
            $table->unsignedInteger('round_number');
            $table->string('level', 16);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['quality_process_id', 'quality_stage_id'], 'qcl_run_stage_unique');
        });
        Schema::create('quality_checklist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_checklist_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_item_id')->nullable()->constrained('quality_checklist_items')->nullOnDelete();
            $table->string('section_name');
            $table->string('code', 20);
            $table->text('question');
            $table->string('severity', 16);
            $table->string('severity_qa', 16);
            $table->unsignedInteger('sort_order');
            $table->string('status', 24)->default('PENDING');
            $table->text('observation')->nullable();
            $table->text('resolution')->nullable();
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignUuid('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->unique(['quality_checklist_run_id', 'code']);
            $table->index(['quality_checklist_run_id', 'status']);
        });

        $template  = DB::table('quality_checklist_templates')->insertGetId(['name' => 'EDP - Checklist de Qualidade em Projetos de Distribuição', 'version' => '1.0', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $sections  = [];
        $sectionId = function (string $name) use (&$sections, $template) { return $sections[$name] ??= DB::table('quality_checklist_sections')->insertGetId(['quality_checklist_template_id' => $template, 'name' => $name, 'sort_order' => count($sections) + 1, 'created_at' => now(), 'updated_at' => now()]); };
        $items     = [
            ['Levantamento e anteprojeto', 'Informações do local conferidas conforme AP (PDF e croqui)?', 'Grave', 'Grave'], ['Levantamento e anteprojeto', 'Rascunho compatível com geometria, acesso, chuva, fibra e contato com cliente?', 'Grave', 'Grave'], ['Rede e condutores', 'Bitola de secundário e primário compatível com potência, cabos existentes e AP?', 'Grave', 'Grave'], ['Levantamento e anteprojeto', 'Croqui do levantador conforme segurança, padrões construtivos e AP?', 'Grave', 'Atenção'], ['Rede e condutores', 'Cabo piloto mantido em rede aberta e retirado em multiplexada?', 'Atenção', 'Atenção'], ['Levantamento e anteprojeto', 'Observações do levantador foram inseridas no desenho?', 'Grave', 'Grave'], ['Rede e condutores', 'RCAL185-Q usado em região litorânea até 300 m da orla?', 'Grave', 'Grave'], ['Postes e estruturas', 'Não foram usados equipamentos em postes de travessia?', 'Grave', 'Atenção'], ['Rede e condutores', 'Neutro removido e interligado à cordoalha sem rede secundária?', 'Atenção', 'Grave'], ['Postes e estruturas', 'Poste de madeira ou em mau estado foi substituído?', 'Grave', 'Grave'], ['Postes e estruturas', 'Poste coerente com esforço; 600 daN em FL, transições, derivações e equipamentos; 12 m na primária?', 'Grave', 'Grave'], ['Postes e estruturas', 'Travessias e afastamentos atendem à distância mínima?', 'Grave', 'Grave'], ['Equipamentos', 'BCA ou BCD projetado conforme AP?', 'Grave', 'Grave'], ['Levantamento e anteprojeto', 'Contorno para redução do CHI e estudo de anteprojeto foram avaliados?', 'Atenção', 'Atenção'], ['Equipamentos', 'Rede secundária multiplexada para religadores e banco de capacitores?', 'Grave', 'Grave'], ['Equipamentos', 'Transformador projetado para carga e demanda do circuito?', 'Grave', 'Grave'], ['Equipamentos', 'Materiais de transformador em bandeira foram orçados?', 'Grave', 'Atenção'], ['Desenho e documentação', 'Metragem dos cabos em todos os vãos e rede adjacente nas extremidades?', 'Grave', 'Grave'], ['Equipamentos', 'PEP correto informado em notas de RL/RE para laminar ou trocar chave?', 'Grave', 'Grave'], ['Equipamentos', 'RE em poste de 10 m por 600 daN?', 'Grave', 'Grave'], ['Desenho e documentação', 'Remoção com parecer técnico, fotos e identificação/data do poste?', 'Atenção', 'Grave'], ['Desenho e documentação', 'Relatório ponto a ponto anexado e projeto anexado ao SAP?', 'Grave', 'Grave'], ['Cadastro e orçamento', 'Acerto cadastral realizado na base e regional avisada quando necessário?', 'Atenção', 'Grave'], ['Cadastro e orçamento', 'Metragem e UAR validados em divisa urbana/rural?', 'Grave', 'Grave'], ['Desenho e documentação', 'Ferramenta da Qualidade aplicada?', 'Grave', 'Grave'], ['Desenho e documentação', 'Desenho liberado para construção?', 'Grave', 'Grave'], ['Desenho e documentação', 'Comparação realizada?', 'Grave', 'Grave'], ['Postes e estruturas', 'Estruturas definidas e associadas aos postes?', 'Atenção', 'Grave'], ['Equipamentos', 'Ativos da chave substituídos em substituição de CF?', 'Atenção', 'Grave'], ['Equipamentos', 'Ativos da chave padronizados em substituição de BF ou FF?', 'Atenção', 'Grave'], ['Cadastro e orçamento', 'Orçamentação de RL, RE e BC conferida?', 'Atenção', 'Atenção'], ['Rede e condutores', 'Quantidade de espaçadores informada nos vãos?', 'Atenção', 'Atenção'], ['Desenho e documentação', 'Direção de fonte e carga informada quando aplicável?', 'Grave', 'Grave'], ['Desenho e documentação', 'Divisas e todas as etapas de RDA indicadas quando aplicável?', 'Grave', 'Grave'], ['Postes e estruturas', 'Posição das estruturas indicada para mais de um circuito no poste?', 'Grave', 'Atenção'], ['Postes e estruturas', 'Necessidade de aprumar poste de concreto indicada?', 'Grave', 'Grave'], ['Postes e estruturas', 'Divisa, metragem e direção da implantação de novo poste indicadas?', 'Atenção', 'Grave'], ['Postes e estruturas', 'Remoção/substituição de estai e cálculo de esforço indicados?', 'Atenção', 'Grave'], ['Desenho e documentação', 'Nomenclaturas conforme padrão?', 'Atenção', 'Grave'], ['Equipamentos', 'Número, elo e potência dos equipamentos informados?', 'Atenção', 'Grave'], ['Equipamentos', 'Estrutura da chave compatível com o local?', 'Grave', 'Grave'], ['Equipamentos', 'Necessidade de padronizar ET informada?', 'Grave', 'Grave'], ['Equipamentos', 'Direção de by-pass/fonte/carga e chave projetada em RL/RE indicadas?', 'Grave', 'Grave'], ['Ramais', 'Tipo, quantidade e metragem dos ramais (inclusive retirada) indicados?', 'Grave', 'Grave'], ['Ramais', 'Baldeação ou substituição do ramal para poste próximo indicada quando necessária?', 'Grave', 'Grave'], ['Ramais', 'Ramal clandestino informado?', 'Atenção', 'Grave'], ['Ramais', 'Substituição de ramal em obra de poste foi justificada?', 'Atenção', 'Atenção'], ['Aterramento', 'Aterramento indicado para equipamentos, FL e transições?', 'Grave', 'Grave'], ['Aterramento', 'P.A.T. instalado nos pontos exigidos?', 'Grave', 'Grave'], ['Desenho e documentação', 'Nomes de ruas e avenidas indicados?', 'Atenção', 'Grave'], ['Desenho e documentação', 'Nota de rede compartilhante inserida?', 'Atenção', 'Grave'], ['Desenho e documentação', 'Podas, cercas-vivas ou corte de bambus informados?', 'Grave', 'Grave'], ['Postes e estruturas', 'Concretagem para 1000 daN, equipamentos ou solo irregular informada?', 'Grave', 'Grave'], ['Desenho e documentação', 'Refazer passeio (Q/R) informado, inclusive aterramentos?', 'Grave', 'Grave'], ['Desenho e documentação', 'Pontos enumerados conforme Kaffa?', 'Atenção', 'Grave'], ['Desenho e documentação', 'Cabeçalho, PEP, endereço e interessado preenchidos corretamente?', 'Atenção', 'Grave'], ['Desenho e documentação', 'Desenho plotado em PDF?', 'Grave', 'Grave'], ['Desenho e documentação', 'QR Code inserido no PDF?', 'Atenção', 'Atenção'], ['BT0', 'Toda a rede secundária foi removida?', 'Grave', 'Grave'], ['BT0', 'Cordoalha 30054507 instalada em todos os vãos?', 'Grave', 'Grave'], ['BT0', 'Transformador exclusivo mantido, porta informada e blindagem indicada?', 'Atenção', 'Grave'], ['BT0', 'Transformador instalado conforme demanda (um a cada 100 m por ramal)?', 'Grave', 'Grave'], ['BT0', 'Iluminação pública realocada?', 'Grave', 'Grave'], ['BT0', 'Concentradores secundários conforme demanda e esforço do poste?', 'Grave', 'Grave'], ['BT0', 'Remota e concentrador primário orçados na primeira etapa?', 'Grave', 'Grave'], ['BT seletivo', 'Secundário multiplexado?', 'Grave', 'Grave'], ['BT seletivo', 'Orçamentação conferida, sem pagamento ou cobrança duplicada?', 'Grave', 'Grave'], ['BT seletivo', 'Quantidade de CS por poste compatível com ramais?', 'Grave', 'Grave'], ['BT seletivo', 'AF informado junto à estrutura conforme padrão?', 'Grave', 'Grave'], ['BT0 e BT seletivo', 'Clientes monofásicos, bifásicos e trifásicos por ponto informados?', 'Grave', 'Grave'], ['Dimensionamento e conferência', 'Solução de menor custo de interferência adotada?', 'Grave', 'Grave'], ['Postes e estruturas', 'Estruturas compatíveis com o ângulo do local?', 'Grave', 'Grave'], ['Rede e condutores', 'Condutor secundário dimensionado conforme demanda?', 'Grave', 'Grave'], ['Dimensionamento e conferência', 'Projeto desenhado e orçado corresponde ao levantamento?', 'Grave', 'Grave'], ['Cadastro e orçamento', 'Famílias separadas e UAR compatível com mão de obra e desenho?', 'Grave', 'Atenção'],
        ];

        foreach ($items as $index => [$section, $question, $sp, $qa]) {
            DB::table('quality_checklist_items')->insert(['quality_checklist_section_id' => $sectionId($section), 'code' => 'EDP-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT), 'question' => $question, 'severity_sp' => $sp, 'severity_qa' => $qa, 'active' => true, 'sort_order' => $index + 1, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('quality_checklist_entries');
        Schema::dropIfExists('quality_checklist_runs');
        Schema::dropIfExists('quality_checklist_items');
        Schema::dropIfExists('quality_checklist_sections');
        Schema::dropIfExists('quality_checklist_templates');
    }
};
