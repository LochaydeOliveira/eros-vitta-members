<?php
// Sistema ValidaPro - Versão 2.0
require_once 'includes/init.php';

// Finalizar inicialização
finalizeInit();

// Verificar login
requireLogin();

// Verificar timeout da sessão
checkSessionTimeout();

// Renovar sessão
renewSession();

$user = getCurrentUser();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        error_log("CSRF token ausente");
        header('Location: index.php?error=csrf');
        exit();
    } elseif (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log("CSRF token inválido");
        header('Location: index.php?error=csrf');
        exit();
    }
    
    $promessa_principal = $_POST['promessa_principal'] ?? '';
    $cliente_consciente = $_POST['cliente_consciente'] ?? '';
    $beneficios = $_POST['beneficios'] ?? '';
    $mecanismo_unico = $_POST['mecanismo_unico'] ?? '';
    $checklist = $_POST['checklist'] ?? [];
    
    // Calcular pontos
    $pontos = count($checklist);
    
    // Determinar nota final e mensagem
    if ($pontos >= 8) {
        $nota_final = $pontos;
        $mensagem = "Produto com alto potencial!";
        $cor_classe = "text-green-600";
        $bg_classe = "bg-green-100";
        $icon = "fas fa-trophy";
        $recomendacao = "Seu produto tem excelente potencial! Foque em criar campanhas de marketing agressivas, ampliar canais de venda e investir em branding para consolidar sua marca.";
        $proximos_passos = [
            "Lance campanhas de Facebook Ads e Google Ads segmentadas para o público-alvo.",
            "Implemente estratégias de remarketing para aumentar conversão.",
            "Crie uma página de vendas otimizada com provas sociais (depoimentos, avaliações).",
            "Invista em parcerias com influenciadores do nicho.",
            "Monitore métricas como ROI, CAC e LTV semanalmente.",
            "Considere expandir para marketplaces ou afiliados."
        ];
    } elseif ($pontos >= 5) {
        $nota_final = $pontos;
        $mensagem = "Produto razoável, com potencial";
        $cor_classe = "text-yellow-600";
        $bg_classe = "bg-yellow-100";
        $icon = "fas fa-star";
        $recomendacao = "Seu produto tem potencial, mas precisa de ajustes. Foque em identificar e melhorar os pontos fracos antes de escalar o investimento.";
        $proximos_passos = [
            "Analise os critérios não marcados e busque formas de aprimorá-los.",
            "Realize testes A/B em criativos e páginas de venda.",
            "Colete feedback de clientes e ajuste a oferta conforme necessário.",
            "Ajuste o preço ou condições de frete para aumentar competitividade.",
            "Invista em conteúdo para educar o público sobre o diferencial do produto."
        ];
    } else {
        $nota_final = $pontos;
        $mensagem = "Produto fraco, repense a escolha";
        $cor_classe = "text-red-600";
        $bg_classe = "bg-red-100";
        $icon = "fas fa-exclamation-triangle";
        $recomendacao = "Este produto pode não ser a melhor escolha no momento. Reavalie o nicho, procure alternativas ou faça mudanças significativas na oferta.";
        $proximos_passos = [
            "Pesquise produtos alternativos com maior demanda ou menos concorrência.",
            "Analise os principais concorrentes e identifique oportunidades de diferenciação.",
            "Considere mudar o nicho ou público-alvo.",
            "Participe de grupos e fóruns para identificar tendências emergentes.",
            "Reveja sua estratégia de marketing e proposta de valor."
        ];
    }
    
    // Análise detalhada dos pontos
    $analise_pontos = [];
    $itens_analise = [
        'vida_mais_facil' => ['nome' => 'Deixa a vida mais fácil', 'peso' => 1.2],
        'criativos_dinamicos' => ['nome' => 'Criativos dinâmicos', 'peso' => 1.0],
        'buscas_google' => ['nome' => 'Buscas no Google', 'peso' => 1.5],
        'vendido_lojas' => ['nome' => 'Já vendido em lojas', 'peso' => 1.3],
        'economiza_dinheiro' => ['nome' => 'Economiza dinheiro', 'peso' => 1.4],
        'economiza_tempo' => ['nome' => 'Economiza tempo', 'peso' => 1.1],
        'nao_nicho_sensivel' => ['nome' => 'Não é nicho sensível', 'peso' => 1.0],
        'menos_50_dolares' => ['nome' => 'Menos de $50', 'peso' => 1.2],
        'so_internet' => ['nome' => 'Só na internet', 'peso' => 1.3],
        'nao_commodity' => ['nome' => 'Não é commodity', 'peso' => 1.1]
    ];
    
    foreach ($itens_analise as $key => $item) {
        $marcado = in_array($key, $checklist);
        $analise_pontos[] = [
            'nome' => $item['nome'],
            'marcado' => $marcado,
            'peso' => $item['peso'],
            'pontos' => $marcado ? $item['peso'] : 0
        ];
    }
    
    // Salvar no banco de dados
    try {
        $stmt = $pdo->prepare("
            INSERT INTO results (user_id, promessa_principal, cliente_consciente, beneficios, mecanismo_unico, pontos, nota_final, mensagem) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'],
            $promessa_principal,
            $cliente_consciente,
            $beneficios,
            $mecanismo_unico,
            $pontos,
            $nota_final,
            $mensagem
        ]);
        $resultado_id = $pdo->lastInsertId(); // Obter o ID do resultado inserido
    } catch (PDOException $e) {
        error_log("Erro ao salvar resultado: " . $e->getMessage());
    }
} else {
    // Redirecionar se não for POST
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado - ValidaPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-chart-line text-white"></i>
                    </div>
                    <h1 class="text-xl font-bold text-gray-800">ValidaPro</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">
                        <i class="fas fa-user mr-1"></i>
                        <?php echo htmlspecialchars($user['name']); ?>
                    </span>
                    <a href=logout.php class="text-red-600 hover:text-red-800 text-sm font-medium">
                        <i class="fas fa-sign-out-alt mr-1"></i>Sair
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Resultado Principal -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Resultado da Análise</h2>
                
                <!-- Nota Final -->
                <div class="mb-6">
                    <div class="inline-flex items-center justify-center w-32 h-32 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-4xl font-bold mb-4">
                        <?php echo $nota_final; ?>/10
                    </div>
                    <p class="text-lg text-gray-600">Sua pontuação final</p>
                </div>
                
                <!-- Mensagem -->
                <div class="<?php echo $bg_classe; ?> rounded-xl p-6 mb-6">
                    <div class="flex items-center justify-center">
                        <i class="<?php echo $icon; ?> text-3xl <?php echo $cor_classe; ?> mr-4"></i>
                        <h3 class="text-2xl font-bold <?php echo $cor_classe; ?>"><?php echo $mensagem; ?></h3>
                    </div>
                </div>
                
                <!-- Ações Principais -->
                <div class="flex flex-wrap justify-center gap-4 mt-8">
                    <a href="index.php" 
                       class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-semibold rounded-lg hover:from-blue-600 hover:to-indigo-700 transition duration-200 transform hover:scale-105">
                        <i class="fas fa-plus mr-2"></i>
                        Nova Análise
                    </a>
                    
                    <button onclick="exportarPDF()" 
                            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold rounded-lg hover:from-green-600 hover:to-emerald-700 transition duration-200 transform hover:scale-105">
                        <i class="fas fa-file-pdf mr-2"></i>
                        Exportar PDF
                    </button>
                    
                    <button onclick="compartilharResultado()" 
                            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-600 hover:to-pink-700 transition duration-200 transform hover:scale-105">
                        <i class="fas fa-share-alt mr-2"></i>
                        Compartilhar
                    </button>
                    
                    <button onclick="salvarResultado()" 
                            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-gray-500 to-gray-600 text-white font-semibold rounded-lg hover:from-gray-600 hover:to-gray-700 transition duration-200 transform hover:scale-105">
                        <i class="fas fa-save mr-2"></i>
                        Salvar
                    </button>
                </div>
            </div>
        </div>

        <!-- Gráfico de Análise -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-chart-bar mr-3 text-blue-600"></i>
                Análise Detalhada
            </h3>
            
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Gráfico de Barras -->
                <div>
                    <h4 class="text-lg font-semibold text-gray-700 mb-4">Pontuação por Critério</h4>
                    <canvas id="analiseChart" width="400" height="300"></canvas>
                </div>
                
                <!-- Resumo dos Pontos -->
                <div>
                    <h4 class="text-lg font-semibold text-gray-700 mb-4">Resumo dos Critérios</h4>
                    <div class="space-y-3">
                        <?php foreach ($analise_pontos as $item): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="<?php echo $item['marcado'] ? 'fas fa-check-circle text-green-600' : 'fas fa-times-circle text-gray-400'; ?> mr-3"></i>
                                <span class="<?php echo $item['marcado'] ? 'text-gray-800 font-medium' : 'text-gray-500'; ?>">
                                    <?php echo $item['nome']; ?>
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-medium <?php echo $item['marcado'] ? 'text-green-600' : 'text-gray-400'; ?>">
                                    <?php echo $item['marcado'] ? '+' . $item['peso'] : '0'; ?> pts
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recomendações -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-lightbulb mr-3 text-yellow-500"></i>
                Recomendações Personalizadas
            </h3>
            
            <div class="bg-blue-50 rounded-xl p-6 mb-6">
                <h4 class="text-lg font-semibold text-blue-800 mb-3">Análise do Especialista</h4>
                <p class="text-blue-700 leading-relaxed"><?php echo $recomendacao; ?></p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-rocket text-green-600 text-2xl mr-3"></i>
                        <h5 class="text-lg font-semibold text-green-800">Próximos Passos</h5>
                    </div>
                    <ul class="space-y-2">
                        <?php foreach ($proximos_passos as $passo): ?>
                        <li class="flex items-start">
                            <i class="fas fa-arrow-right text-green-500 mt-1 mr-2 text-sm"></i>
                            <span class="text-green-700 text-sm"><?php echo $passo; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-chart-line text-blue-600 text-2xl mr-3"></i>
                        <h5 class="text-lg font-semibold text-blue-800">Métricas Importantes</h5>
                    </div>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-eye text-blue-500 mt-1 mr-2 text-sm"></i>
                            <span class="text-blue-700 text-sm">Taxa de conversão</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-dollar-sign text-blue-500 mt-1 mr-2 text-sm"></i>
                            <span class="text-blue-700 text-sm">ROI das campanhas</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-users text-blue-500 mt-1 mr-2 text-sm"></i>
                            <span class="text-blue-700 text-sm">Engajamento do público</span>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-tools text-purple-600 text-2xl mr-3"></i>
                        <h5 class="text-lg font-semibold text-purple-800">Ferramentas Úteis</h5>
                    </div>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-search text-purple-500 mt-1 mr-2 text-sm"></i>
                            <span class="text-purple-700 text-sm">Google Trends</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-ad text-purple-500 mt-1 mr-2 text-sm"></i>
                            <span class="text-purple-700 text-sm">Facebook Ads Manager</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-analytics text-purple-500 mt-1 mr-2 text-sm"></i>
                            <span class="text-purple-700 text-sm">Google Analytics</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Respostas das Perguntas -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-clipboard-list mr-3 text-blue-600"></i>
                Suas Respostas
            </h3>
            
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">Promessa Principal</h4>
                    <div class="bg-gray-50 rounded-lg p-4 text-gray-800">
                        <?php echo nl2br(htmlspecialchars($promessa_principal)); ?>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">Cliente Consciente</h4>
                    <div class="bg-gray-50 rounded-lg p-4 text-gray-800">
                        <?php echo nl2br(htmlspecialchars($cliente_consciente)); ?>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">Benefícios</h4>
                    <div class="bg-gray-50 rounded-lg p-4 text-gray-800">
                        <?php echo nl2br(htmlspecialchars($beneficios)); ?>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">Mecanismo Único</h4>
                    <div class="bg-gray-50 rounded-lg p-4 text-gray-800">
                        <?php echo nl2br(htmlspecialchars($mecanismo_unico)); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="text-center space-x-4">
            <a href="index.php" 
               class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition duration-200">
                <i class="fas fa-plus mr-2"></i>
                Nova Análise
            </a>
            
            <button onclick="window.print()" 
                    class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 transition duration-200">
                <i class="fas fa-print mr-2"></i>
                Imprimir Resultado
            </button>
        </div>
    </div>

    <script>
        // Dados para o gráfico
        const dados = <?php echo json_encode($analise_pontos); ?>;
        
        // Configurar gráfico
        const ctx = document.getElementById('analiseChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dados.map(item => item.nome),
                datasets: [{
                    label: 'Pontuação',
                    data: dados.map(item => item.pontos),
                    backgroundColor: dados.map(item => item.marcado ? 'rgba(34, 197, 94, 0.8)' : 'rgba(156, 163, 175, 0.5)'),
                    borderColor: dados.map(item => item.marcado ? 'rgba(34, 197, 94, 1)' : 'rgba(156, 163, 175, 1)'),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 2,
                        ticks: {
                            stepSize: 0.5
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Funções de ação
        function exportarPDF() {
            // Mostrar loading
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Gerando PDF...';
            btn.disabled = true;
            
            // Redirecionar para o arquivo de exportação
            const resultadoId = <?php echo $resultado_id ?? 'null'; ?>;
            if (resultadoId) {
                window.open('exportar-pdf.php?id=' + resultadoId, '_blank');
            } else {
                alert('Erro: ID do resultado não encontrado');
            }
            
            // Restaurar botão
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 1000);
        }

        function compartilharResultado() {
            const resultado = {
                pontuacao: <?php echo $nota_final; ?>,
                mensagem: "<?php echo $mensagem; ?>",
                url: window.location.href
            };
            
            if (navigator.share) {
                navigator.share({
                    title: 'Resultado da Análise de Produto',
                    text: `Minha análise: ${resultado.pontuacao}/10 - ${resultado.mensagem}`,
                    url: resultado.url
                });
            } else {
                // Fallback para copiar link
                navigator.clipboard.writeText(resultado.url).then(() => {
                    alert('Link copiado para a área de transferência!');
                });
            }
        }

        function salvarResultado() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Salvando...';
            btn.disabled = true;
            
            // Simular salvamento
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-check mr-2"></i>Salvo!';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 2000);
            }, 1000);
        }

        // Animar entrada dos elementos
        document.addEventListener('DOMContentLoaded', function() {
            const elementos = document.querySelectorAll('.bg-white');
            elementos.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(20px)';
                    el.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 200);
            });
        });
    </script>
</body>
</html> 