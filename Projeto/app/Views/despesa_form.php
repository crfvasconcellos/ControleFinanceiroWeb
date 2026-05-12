<?php
// Lógica de visualização avançada (Zero JS)

$mesesPt = [
    '01'=>'Janeiro', '02'=>'Fevereiro', '03'=>'Março', '04'=>'Abril', 
    '05'=>'Maio', '06'=>'Junho', '07'=>'Julho', '08'=>'Agosto', 
    '09'=>'Setembro', '10'=>'Outubro', '11'=>'Novembro', '12'=>'Dezembro'
];

$mesFiltro = $_GET['mes'] ?? date('Y-m');
$prioridadeFiltro = $_GET['prioridade'] ?? 'todas';
$tipoFiltro = $_GET['tipo'] ?? 'todas';

function getIconForExpense($name) {
    $n = strtolower($name);
    if (str_contains($n, 'mercado') || str_contains($n, 'comida') || str_contains($n, 'restaurante')) return '🛒';
    if (str_contains($n, 'luz') || str_contains($n, 'energia')) return '⚡';
    if (str_contains($n, 'água') || str_contains($n, 'agua')) return '💧';
    if (str_contains($n, 'internet') || str_contains($n, 'celular') || str_contains($n, 'telefone')) return '📶';
    if (str_contains($n, 'carro') || str_contains($n, 'gasolina') || str_contains($n, 'transporte')) return '🚗';
    if (str_contains($n, 'farmácia') || str_contains($n, 'saúde') || str_contains($n, 'médico')) return '💊';
    if (str_contains($n, 'lazer') || str_contains($n, 'cinema') || str_contains($n, 'streaming')) return '🍿';
    return '📄';
}

$cores = ['#0ea5e9', '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef'];

$despesasFiltradas = [];
$gastosPorCategoria = [];
$totalMes = 0;
$totalEntradasFiltradas = 0;

// --- Filtragem das transações ---
foreach ($todasTransacoes as $transacao) {
    $mesDespesa = substr($transacao['data'], 0, 7);

    if ($mesFiltro !== 'todos' && $mesDespesa !== $mesFiltro) continue;
    if ($tipoFiltro !== 'todas' && $transacao['tipo'] !== $tipoFiltro) continue;

    $prioridade = 'baixa';
    if ($transacao['tipo'] === 'entrada') {
        $prioridade = 'entrada';
        $icone = !empty($transacao['icone']) ? $transacao['icone'] : '💵';
    } else {
        if ($transacao['valor'] > 500) $prioridade = 'alta';
        elseif ($transacao['valor'] > 100) $prioridade = 'media';
        
        if ($prioridadeFiltro !== 'todas' && $prioridade !== $prioridadeFiltro) continue;
        $icone = !empty($transacao['icone']) ? $transacao['icone'] : getIconForExpense($transacao['nome']);
    }

    $transacao['prioridade'] = $prioridade;
    $transacao['icone'] = $icone;
    $despesasFiltradas[] = $transacao;
    if ($transacao['tipo'] === 'saida') {
        $totalMes += $transacao['valor'];
    }
    if ($transacao['tipo'] === 'entrada') {
        $totalEntradasFiltradas += $transacao['valor'];
    }
}

// Recalcula saldo com base nos filtros aplicados
$saldoDisponivel = $totalEntradasFiltradas - $totalMes;

// --- Gráfico dinâmico baseado no filtro ---
$evolucaoGrafico = [];
$chartTitle = 'Gastos Mensais';

if ($mesFiltro === 'todos') {
    // Modo "Todos": mostra gastos mensais (últimos 6 meses)
    for ($i = 5; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-$i months"));
        $evolucaoGrafico[$m] = ['gastos' => 0, 'label' => substr($mesesPt[date('m', strtotime("-$i months"))], 0, 3)];
    }
    foreach ($todasTransacoes as $t) {
        $mk = substr($t['data'], 0, 7);
        if ($t['tipo'] === 'saida' && isset($evolucaoGrafico[$mk])) {
            $evolucaoGrafico[$mk]['gastos'] += $t['valor'];
        }
    }
} else {
    // Modo mês específico: mostra gastos diários do mês
    $chartTitle = 'Gastos Diários — ' . $mesesPt[substr($mesFiltro, 5, 2)] . ' ' . substr($mesFiltro, 0, 4);
    $ano = (int)substr($mesFiltro, 0, 4);
    $mesNum = (int)substr($mesFiltro, 5, 2);
    $diasNoMes = cal_days_in_month(CAL_GREGORIAN, $mesNum, $ano);
    
    for ($d = 1; $d <= $diasNoMes; $d++) {
        $dk = sprintf('%s-%02d', $mesFiltro, $d);
        $evolucaoGrafico[$dk] = ['gastos' => 0, 'label' => (string)$d];
    }
    foreach ($todasTransacoes as $t) {
        if ($t['tipo'] === 'saida' && isset($evolucaoGrafico[$t['data']])) {
            $evolucaoGrafico[$t['data']]['gastos'] += $t['valor'];
        }
    }
}

// --- Construir pontos do SVG ---
$svgWidth = 1000;
$svgHeight = 300;
$svgPoints = [];
$valores = array_column($evolucaoGrafico, 'gastos');
$maxVal = count($valores) > 0 ? max($valores) : 0;
if ($maxVal == 0) $maxVal = 1;
$numPontos = count($evolucaoGrafico);
$svgStepX = $numPontos > 1 ? $svgWidth / ($numPontos - 1) : $svgWidth;
$currentX = 0;

// Para mês específico com muitos dias, mostrar apenas alguns labels
$mostrarTodosLabels = ($numPontos <= 12);

foreach ($evolucaoGrafico as $k => $item) {
    $currentY = $svgHeight - (($item['gastos'] / $maxVal) * ($svgHeight * 0.85));
    $label = $item['label'];
    // Para dias, mostrar label a cada 5 dias
    if (!$mostrarTodosLabels) {
        $diaNum = (int)$label;
        if ($diaNum !== 1 && $diaNum % 5 !== 0 && $diaNum !== $diasNoMes) {
            $label = '';
        }
    }
    $svgPoints[] = ['x' => $currentX, 'y' => $currentY, 'val' => $item['gastos'], 'label' => $label];
    $currentX += $svgStepX;
}

$pathD = "";
if (count($svgPoints) > 0) {
    $pathD = "M " . $svgPoints[0]['x'] . "," . $svgPoints[0]['y'];
    for ($i = 1; $i < count($svgPoints); $i++) {
        $pathD .= " L " . $svgPoints[$i]['x'] . "," . $svgPoints[$i]['y'];
    }
}
$lastX = end($svgPoints)['x'] ?? $svgWidth;
$areaD = $pathD . " L $lastX,$svgHeight L 0,$svgHeight Z";
$temDadosGrafico = max($valores) > 0;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle Financeiro - Dashboard</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="top-bar">
        <div class="top-bar__inner">
            <div class="top-bar__user" style="display:flex; gap:1.5rem; align-items: center;">
                <div style="display:flex; align-items: center; gap:0.8rem;">
                    <img src="assets/img/logo.png" alt="Logo" style="width: 40px; height: 40px; object-fit: contain; border-radius: 50%; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" draggable="false" ondragstart="return false" onselectstart="return false">
                    <span class="top-bar__name">Olá, <?= htmlspecialchars($userNome) ?></span>
                </div>
                <a href="index.php?route=logout" class="btn btn-outline btn-sm">Sair</a>
            </div>
            <div style="display:flex; gap:1rem; align-items: center;">
                <a href="#modalNovaDespesa" class="btn btn-danger">Despesa</a>
                <a href="#modalAdicionarSaldo" class="btn btn-success">Saldo</a>
            </div>
        </div>
    </header>

    <main class="container">
        
        <!-- TOP ROW: Resumo Horizontal -->
        <section class="summary-row">
            <div class="stat-card">
                <div class="stat-card__icon icon-blue">💰</div>
                <div class="stat-card__label">Gastos no Período</div>
                <div class="stat-card__value">R$ <?= number_format($totalMes, 2, ',', '.') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon icon-emerald">💵</div>
                <div class="stat-card__label">Saldo Disponível</div>
                <div class="stat-card__value <?= $saldoDisponivel < 0 ? 'text-danger' : 'text-success' ?>">R$ <?= number_format($saldoDisponivel, 2, ',', '.') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon icon-purple">📋</div>
                <div class="stat-card__label">Transações</div>
                <div class="stat-card__value"><?= count($despesasFiltradas) ?></div>
            </div>
        </section>

        <!-- MAIN GRID: Gráficos e Lista (Stack layout) -->
        <div class="grid" style="grid-template-columns: 1fr;">
            <!-- Topo: Gráficos -->
            <section>
                
                <div class="chart-container">
                    <h3 class="chart-title"><?= htmlspecialchars($chartTitle) ?></h3>
                    <?php if(!$temDadosGrafico): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📈</div>
                            <div class="empty-title">Sem Dados Suficientes</div>
                            <div class="empty-desc">Adicione transações para ver a sua evolução financeira.</div>
                        </div>
                    <?php else: ?>
                        <div class="area-chart">
                            <svg viewBox="0 -10 <?= $svgWidth ?> <?= $svgHeight + 30 ?>">
                                <defs>
                                    <linearGradient id="areaGradient" x1="0" x2="0" y1="0" y2="1">
                                        <stop offset="0%" stop-color="var(--color-primary)" stop-opacity="0.8"/>
                                        <stop offset="100%" stop-color="var(--color-primary)" stop-opacity="0"/>
                                    </linearGradient>
                                </defs>
                                
                                <line class="grid-line" x1="0" y1="<?= $svgHeight * 0.33 ?>" x2="<?= $svgWidth ?>" y2="<?= $svgHeight * 0.33 ?>" />
                                <line class="grid-line" x1="0" y1="<?= $svgHeight * 0.66 ?>" x2="<?= $svgWidth ?>" y2="<?= $svgHeight * 0.66 ?>" />
                                <line class="grid-line" x1="0" y1="<?= $svgHeight ?>" x2="<?= $svgWidth ?>" y2="<?= $svgHeight ?>" />

                                <?php if(count($svgPoints) > 0): ?>
                                    <path class="area-fill" d="<?= $areaD ?>" />
                                    <path class="data-path" d="<?= $pathD ?>" />
                                    
                                    <?php foreach($svgPoints as $pt): ?>
                                        <circle class="data-point" cx="<?= $pt['x'] ?>" cy="<?= $pt['y'] ?>" r="4">
                                            <title>R$ <?= number_format($pt['val'], 2, ',', '.') ?></title>
                                        </circle>
                                        <text x="<?= $pt['x'] ?>" y="<?= $svgHeight + 22 ?>" text-anchor="middle"><?= $pt['label'] ?></text>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Direita: Lista e Filtros -->
            <section class="card" style="align-self: start;">
                <div class="flex justify-between items-center mb-4">
                    <h2>Transações</h2>
                    <div style="display:flex; gap:0.5rem;">
                        <a href="#modalHistorico" class="btn btn-outline btn-sm" title="Lixeira">🗑️</a>
                    </div>
                </div>

                <form method="GET" action="index.php" class="filter-bar mb-4">
                    <input type="hidden" name="route" value="dashboard">
                    <div class="filter-select-wrapper">
                        <select name="mes" class="filter-btn">
                            <option value="todos" <?= $mesFiltro === 'todos' ? 'selected' : '' ?>>Todos os Meses</option>
                            <?php for ($i = 0; $i < 12; $i++): 
                                $m = date('Y-m', strtotime("-$i months"));
                                $mesStr = $mesesPt[date('m', strtotime("-$i months"))];
                                $anoStr = date('Y', strtotime("-$i months"));
                            ?>
                                <option value="<?= $m ?>" <?= $mesFiltro === $m ? 'selected' : '' ?>><?= "$mesStr $anoStr" ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-select-wrapper">
                        <select name="prioridade" class="filter-btn">
                            <option value="todas" <?= $prioridadeFiltro === 'todas' ? 'selected' : '' ?>>Qualquer Valor</option>
                            <option value="alta" <?= $prioridadeFiltro === 'alta' ? 'selected' : '' ?>>Alto (> 500)</option>
                            <option value="media" <?= $prioridadeFiltro === 'media' ? 'selected' : '' ?>>Médio (> 100)</option>
                            <option value="baixa" <?= $prioridadeFiltro === 'baixa' ? 'selected' : '' ?>>Baixo</option>
                        </select>
                    </div>
                    <div class="filter-select-wrapper">
                        <select name="tipo" class="filter-btn">
                            <option value="todas" <?= $tipoFiltro === 'todas' ? 'selected' : '' ?>>Todas as Transações</option>
                            <option value="entrada" <?= $tipoFiltro === 'entrada' ? 'selected' : '' ?>>Apenas Entradas</option>
                            <option value="saida" <?= $tipoFiltro === 'saida' ? 'selected' : '' ?>>Apenas Saídas</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                </form>



                <div class="expense-list">
                    <?php if (empty($despesasFiltradas)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <div class="empty-title">Nenhuma Transação</div>
                            <div class="empty-desc">Nenhum registro encontrado para este filtro.</div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($despesasFiltradas as $despesa): ?>
                        <div class="expense-item">
                            <div class="expense-item__info">
                                <div class="expense-item__icon"><?= $despesa['icone'] ?></div>
                                <div class="expense-item__details">
                                    <div class="expense-item__name">
                                        <?= htmlspecialchars($despesa['nome']) ?>
                                        <span class="badge badge-<?= $despesa['prioridade'] ?>"><?= $despesa['prioridade'] ?></span>
                                    </div>
                                    <?php if (!empty($despesa['descricao'])): ?>
                                        <div style="font-size: 0.8rem; color: var(--color-text-light); margin-top: 0.1rem;"><?= htmlspecialchars($despesa['descricao']) ?></div>
                                    <?php endif; ?>
                                    <span class="expense-item__date"><?= date('d M, Y', strtotime($despesa['data'])) ?></span>
                                    <?php if (!empty($despesa['comprovante'])): ?>
                                        <a href="<?= htmlspecialchars($despesa['comprovante']) ?>" target="_blank" style="font-size: 0.75rem; color: var(--color-primary); margin-left: 0.5rem; text-decoration: none;">📄 PDF</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="expense-item__actions">
                                <?php if ($despesa['tipo'] === 'entrada'): ?>
                                    <div class="expense-item__value" style="color: var(--color-success);">+ R$ <?= number_format($despesa['valor'], 2, ',', '.') ?></div>
                                <?php else: ?>
                                    <div class="expense-item__value expense-item__value--expense">- R$ <?= number_format($despesa['valor'], 2, ',', '.') ?></div>
                                <?php endif; ?>
                                <div class="expense-actions-btns">
                                    <a href="#modalEditar_<?= htmlspecialchars($despesa['id']) ?>" class="btn-icon" title="Editar">✏️</a>
                                    <a href="#confirmarExcluir_<?= htmlspecialchars($despesa['id']) ?>" class="btn-icon danger" title="Excluir">🗑️</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>



    <div id="modalNovaDespesa" class="modal-overlay">
        <div class="modal-content">
            <a href="#!" class="modal-close">✕</a>
            <h2>Nova Despesa</h2>
            <form method="post" enctype="multipart/form-data" style="margin-top:2rem;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="salvar_transacao">
                <input type="hidden" name="tipo" value="saida">
                
                <div class="form-floating">
                    <input id="new_nome" name="nome" type="text" required placeholder="Ex: Mercado" maxlength="30">
                    <label for="new_nome">Título / Nome</label>
                </div>

                <div class="form-floating">
                    <input id="new_descricao" name="descricao" type="text" placeholder="Ex: Compras do mês" maxlength="150">
                    <label for="new_descricao">Descrição (opcional)</label>
                </div>
                
                <div class="form-floating">
                    <input id="new_valor" name="valor" type="text" required placeholder="Ex: 89.90" maxlength="11">
                    <label for="new_valor">Valor (R$)</label>
                </div>
                
                <div class="form-floating">
                    <input id="new_data" name="data" type="date" required value="<?= date('Y-m-d') ?>">
                    <label for="new_data">Data da Transação</label>
                </div>

                <div style="margin-top: 1rem; padding: 0.9rem; border: 1px dashed var(--color-border); border-radius: var(--radius-md);">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--color-secondary);">
                        <input type="checkbox" name="recorrente_mensal" value="1">
                        Gerar despesa recorrente mensal
                    </label>
                    <div class="form-floating" style="margin-top: 0.75rem; margin-bottom: 0;">
                        <input id="new_recorrencia_meses" name="recorrencia_meses" type="number" min="1" max="24" value="12" placeholder="Quantidade de meses">
                        <label for="new_recorrencia_meses">Quantidade de meses (1 a 24)</label>
                    </div>
                </div>

                <div class="form-floating" style="margin-top: 1.5rem;">
                    <select id="new_icone" name="icone" style="width: 100%; padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text);">
                        <option value="📄">📄 Documento (Padrão)</option>
                        <option value="🛒">🛒 Mercado / Comida</option>
                        <option value="⚡">⚡ Energia / Luz</option>
                        <option value="💧">💧 Água</option>
                        <option value="📶">📶 Internet / Telefone</option>
                        <option value="🚗">🚗 Transporte / Gasolina</option>
                        <option value="💊">💊 Saúde / Farmácia</option>
                        <option value="🍿">🍿 Lazer / Streaming</option>
                        <option value="🏠">🏠 Moradia</option>
                        <option value="🛍️">🛍️ Compras</option>
                    </select>
                    <label for="new_icone" style="font-size: 0.8rem; color: var(--color-text-light); top: -20px; left: 0;">Símbolo (Ícone)</label>
                </div>

                <div class="form-floating" style="margin-top: 1.5rem;">
                    <input id="new_comprovante" name="comprovante" type="file" accept=".pdf" style="padding-top: 1.5rem;">
                    <label for="new_comprovante" style="top: -5px; font-size: 0.85rem;">Comprovante (PDF opcional)</label>
                </div>

                <button type="submit" class="btn btn-danger btn-block mt-4" style="padding: 1rem; font-size:1.05rem;">Registrar Despesa</button>
            </form>
        </div>
    </div>

    <div id="modalAdicionarSaldo" class="modal-overlay">
        <div class="modal-content">
            <a href="#!" class="modal-close">✕</a>
            <h2>Novo Saldo</h2>
            <form method="post" enctype="multipart/form-data" style="margin-top:2rem;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="salvar_transacao">
                <input type="hidden" name="tipo" value="entrada">
                
                <div class="form-floating">
                    <input id="saldo_nome" name="nome" type="text" required placeholder="Ex: Salário" maxlength="30">
                    <label for="saldo_nome">Título / Nome</label>
                </div>

                <div class="form-floating">
                    <input id="saldo_descricao" name="descricao" type="text" placeholder="Ex: Salário de Janeiro" maxlength="150">
                    <label for="saldo_descricao">Descrição (opcional)</label>
                </div>
                
                <div class="form-floating">
                    <input id="saldo_valor" name="valor" type="text" required placeholder="Ex: 1500.00" maxlength="11">
                    <label for="saldo_valor">Valor (R$)</label>
                </div>
                
                <div class="form-floating">
                    <input id="saldo_data" name="data" type="date" required value="<?= date('Y-m-d') ?>">
                    <label for="saldo_data">Data da Transação</label>
                </div>

                <div style="margin-top: 1rem; padding: 0.9rem; border: 1px dashed var(--color-border); border-radius: var(--radius-md);">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--color-secondary);">
                        <input type="checkbox" name="recorrente_mensal" value="1">
                        Gerar saldo recorrente mensal
                    </label>
                    <div class="form-floating" style="margin-top: 0.75rem; margin-bottom: 0;">
                        <input id="saldo_recorrencia_meses" name="recorrencia_meses" type="number" min="1" max="24" value="12" placeholder="Quantidade de meses">
                        <label for="saldo_recorrencia_meses">Quantidade de meses (1 a 24)</label>
                    </div>
                </div>

                <div class="form-floating" style="margin-top: 1.5rem;">
                    <select id="saldo_icone" name="icone" style="width: 100%; padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text);">
                        <option value="💵">💵 Dinheiro (Padrão)</option>
                        <option value="💼">💼 Salário</option>
                        <option value="📈">📈 Rendimento / Investimento</option>
                        <option value="🎁">🎁 Presente</option>
                        <option value="🏦">🏦 Transferência</option>
                        <option value="🤑">🤑 Bônus</option>
                    </select>
                    <label for="saldo_icone" style="font-size: 0.8rem; color: var(--color-text-light); top: -20px; left: 0;">Símbolo (Ícone)</label>
                </div>

                <div class="form-floating" style="margin-top: 1.5rem;">
                    <input id="saldo_comprovante" name="comprovante" type="file" accept=".pdf" style="padding-top: 1.5rem;">
                    <label for="saldo_comprovante" style="top: -5px; font-size: 0.85rem;">Comprovante (PDF opcional)</label>
                </div>

                <button type="submit" class="btn btn-success btn-block mt-4" style="padding: 1rem; font-size:1.05rem;">Adicionar Saldo</button>
            </form>
        </div>
    </div>

    <div id="modalHistorico" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <a href="#!" class="modal-close">✕</a>
            <h2>Lixeira e Histórico Completo</h2>
            <p class="text-sm mb-4" style="color: var(--color-text-light);">Registros riscados foram excluídos e não afetam o saldo total.</p>
            
            <div class="expense-list" style="max-height: 55vh; overflow-y: auto; padding-right: 1rem;">
                <?php foreach ($historicoCompleto as $h):
                    $del = !empty($h['deletado_em']);
                    $tipoHistorico = str_starts_with((string)$h['id'], 'saldo_') ? 'entrada' : 'saida';
                ?>
                    <div class="expense-item <?= $del ? 'expense-item--deleted' : '' ?>">
                        <div class="expense-item__info">
                            <div class="expense-item__details">
                                <div class="expense-item__name">
                                    <?= htmlspecialchars($h['nome']) ?>
                                    <?php if($del): ?><span class="badge badge-alta">Excluída</span><?php endif; ?>
                                </div>
                                <span class="expense-item__date"><?= date('d/m/Y', strtotime($h['data'])) ?></span>
                            </div>
                        </div>
                        <?php if ($tipoHistorico === 'entrada'): ?>
                            <div class="expense-item__value" style="color: var(--color-success);">+ R$ <?= number_format($h['valor'], 2, ',', '.') ?></div>
                        <?php else: ?>
                            <div class="expense-item__value expense-item__value--expense">- R$ <?= number_format($h['valor'], 2, ',', '.') ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Modais de confirmação de exclusão (CSS puro, sem JS) -->
    <?php foreach ($despesasFiltradas as $despesa): ?>
        <div id="confirmarExcluir_<?= htmlspecialchars($despesa['id']) ?>" class="modal-overlay">
            <div class="modal-content" style="max-width: 420px; text-align: center;">
                <a href="#!" class="modal-close">✕</a>
                <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                <h2 style="font-size: 1.3rem; margin-bottom: 0.5rem;">Confirmar Exclusão</h2>
                <p style="color: var(--color-text-light); margin-bottom: 2rem;">
                    Deseja excluir "<strong><?= htmlspecialchars($despesa['nome']) ?></strong>"?<br>
                    Esta ação pode ser desfeita na lixeira.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <a href="#!" class="btn btn-outline">Cancelar</a>
                    <form method="post" action="index.php?route=dashboard" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="remover">
                        <input type="hidden" name="despesa_id" value="<?= htmlspecialchars($despesa['id']) ?>">
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Modais de Edição de Transação -->
    <?php foreach ($despesasFiltradas as $despesa): ?>
        <div id="modalEditar_<?= htmlspecialchars($despesa['id']) ?>" class="modal-overlay">
            <div class="modal-content">
                <a href="#!" class="modal-close">✕</a>
                <h2>Editar Transação</h2>
                <form method="post" enctype="multipart/form-data" style="margin-top:1rem;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="editar_transacao">
                    <input type="hidden" name="transacao_id" value="<?= htmlspecialchars($despesa['id']) ?>">
                    
                    <div class="form-floating">
                        <input id="edit_nome_<?= htmlspecialchars($despesa['id']) ?>" name="nome" type="text" required value="<?= htmlspecialchars($despesa['nome']) ?>" placeholder="Ex: Mercado" maxlength="30">
                        <label for="edit_nome_<?= htmlspecialchars($despesa['id']) ?>">Título / Nome</label>
                    </div>

                    <div class="form-floating">
                        <input id="edit_descricao_<?= htmlspecialchars($despesa['id']) ?>" name="descricao" type="text" value="<?= htmlspecialchars($despesa['descricao'] ?? '') ?>" placeholder="Ex: Detalhes da compra" maxlength="150">
                        <label for="edit_descricao_<?= htmlspecialchars($despesa['id']) ?>">Descrição (opcional)</label>
                    </div>
                    
                    <div class="form-floating">
                        <input id="edit_valor_<?= htmlspecialchars($despesa['id']) ?>" name="valor" type="text" required value="<?= htmlspecialchars($despesa['valor']) ?>" placeholder="Ex: 89.90" maxlength="11">
                        <label for="edit_valor_<?= htmlspecialchars($despesa['id']) ?>">Valor (R$)</label>
                    </div>
                    
                    <div class="form-floating">
                        <input id="edit_data_<?= htmlspecialchars($despesa['id']) ?>" name="data" type="date" required value="<?= htmlspecialchars(substr($despesa['data'], 0, 10)) ?>">
                        <label for="edit_data_<?= htmlspecialchars($despesa['id']) ?>">Data da Transação</label>
                    </div>

                    <div class="form-floating" style="margin-top: 1rem;">
                        <select id="edit_icone_<?= htmlspecialchars($despesa['id']) ?>" name="icone" style="width: 100%; padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text);">
                            <option value="<?= htmlspecialchars($despesa['icone'] ?? '') ?>" selected>Símbolo Atual: <?= htmlspecialchars($despesa['icone'] ?? '') ?></option>
                            <option value="📄">📄 Documento (Padrão)</option>
                            <option value="💵">💵 Dinheiro</option>
                            <option value="🛒">🛒 Mercado / Comida</option>
                            <option value="⚡">⚡ Energia / Luz</option>
                            <option value="💧">💧 Água</option>
                            <option value="📶">📶 Internet / Telefone</option>
                            <option value="🚗">🚗 Transporte / Gasolina</option>
                            <option value="💊">💊 Saúde / Farmácia</option>
                            <option value="🍿">🍿 Lazer / Streaming</option>
                            <option value="🏠">🏠 Moradia</option>
                            <option value="🛍️">🛍️ Compras</option>
                            <option value="💼">💼 Salário</option>
                            <option value="📈">📈 Rendimento / Investimento</option>
                            <option value="🎁">🎁 Presente</option>
                            <option value="🏦">🏦 Transferência</option>
                            <option value="🤑">🤑 Bônus</option>
                        </select>
                        <label for="edit_icone_<?= htmlspecialchars($despesa['id']) ?>" style="font-size: 0.8rem; color: var(--color-text-light); top: -20px; left: 0;">Alterar Símbolo (Ícone)</label>
                    </div>

                    <div class="form-floating" style="margin-top: 1rem;">
                        <input id="edit_comprovante_<?= htmlspecialchars($despesa['id']) ?>" name="comprovante" type="file" accept=".pdf" style="padding-top: 1.5rem;">
                        <label for="edit_comprovante_<?= htmlspecialchars($despesa['id']) ?>" style="top: -5px; font-size: 0.85rem;">Novo Comprovante (PDF opcional)</label>
                    </div>

                    <div style="margin-top: 1rem; padding: 0.9rem; border: 1px dashed var(--color-border); border-radius: var(--radius-md);">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--color-secondary);">
                            <input type="checkbox" name="recorrente_mensal" value="1" <?= ($despesa['recorrente_mensal'] ?? 0) ? 'checked' : '' ?>>
                            Transação recorrente mensal
                        </label>
                        <div class="form-floating" style="margin-top: 0.75rem; margin-bottom: 0;">
                            <input id="edit_recorrencia_meses_<?= htmlspecialchars($despesa['id']) ?>" name="recorrencia_meses" type="number" min="1" max="24" value="<?= htmlspecialchars($despesa['recorrencia_meses'] ?? 1) ?>" placeholder="Quantidade de meses">
                            <label for="edit_recorrencia_meses_<?= htmlspecialchars($despesa['id']) ?>">Quantidade de meses (1 a 24)</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block mt-4" style="padding: 1rem; font-size:1.05rem;">Salvar Alterações</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($errors)): ?>
        <div id="modalErros" class="modal-overlay" style="display: flex; z-index: 1000;">
            <div class="modal-content" style="max-width: 420px; text-align: center;">
                <a href="index.php?route=dashboard" class="modal-close">✕</a>
                <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                <h2 style="font-size: 1.3rem; margin-bottom: 0.5rem;">Aviso</h2>
                <div style="color: var(--color-text-light); margin-bottom: 2rem;">
                    <?php foreach ($errors as $e): ?>
                        <p style="margin-bottom: 0.5rem;"><strong><?= htmlspecialchars($e) ?></strong></p>
                    <?php endforeach; ?>
                </div>
                <div style="display: flex; justify-content: center;">
                    <a href="index.php?route=dashboard" class="btn btn-primary" style="padding-left: 3rem; padding-right: 3rem;">Entendido</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>