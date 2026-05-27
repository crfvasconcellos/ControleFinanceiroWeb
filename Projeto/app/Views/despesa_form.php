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
$categoriaFiltro = $_GET['categoria'] ?? 'todas';
$buscaFiltro = strtolower(trim($_GET['busca'] ?? ''));

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
    $mesTermino = !empty($transacao['data_termino']) ? substr($transacao['data_termino'], 0, 7) : $mesDespesa;

    if ($mesFiltro !== 'todos') {
        if ($mesFiltro < $mesDespesa || $mesFiltro > $mesTermino) continue;
    }
    if ($tipoFiltro !== 'todas' && $transacao['tipo'] !== $tipoFiltro) continue;

    if ($buscaFiltro !== '' && !str_contains(strtolower($transacao['nome']), $buscaFiltro)) continue;

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

    if ($categoriaFiltro !== 'todas' && $icone !== $categoriaFiltro) continue;

    $mesesMultiplicador = 1;
    if ($mesFiltro === 'todos' && !empty($transacao['data_termino'])) {
        $d1 = new DateTime(substr($transacao['data'], 0, 7) . '-01');
        $d2 = new DateTime(substr($transacao['data_termino'], 0, 7) . '-01');
        if ($d2 >= $d1) {
            $diff = $d1->diff($d2);
            $mesesMultiplicador = ($diff->y * 12) + $diff->m + 1;
        }
    }

    $transacao['prioridade'] = $prioridade;
    $transacao['icone'] = $icone;
    $despesasFiltradas[] = $transacao;
    if ($transacao['tipo'] === 'saida') {
        $totalMes += ($transacao['valor'] * $mesesMultiplicador);
    }
    if ($transacao['tipo'] === 'entrada') {
        $totalEntradasFiltradas += ($transacao['valor'] * $mesesMultiplicador);
    }
}

// Recalcula saldo com base nos filtros aplicados
$saldoDisponivel = $totalEntradasFiltradas - $totalMes;

// --- Gráfico dinâmico baseado no filtro ---
$evolucaoGrafico = [];
$chartTitle = 'Gastos Mensais';

if ($mesFiltro === 'todos') {
    // Modo "Todos": mostra gastos mensais (últimos 6 meses)
    // Usa DateTime para evitar bugs do strtotime com meses
    $baseDate = new DateTime('first day of this month');
    for ($i = 5; $i >= 0; $i--) {
        $dt = clone $baseDate;
        $dt->modify("-{$i} months");
        $m = $dt->format('Y-m');
        $mesNum = $dt->format('m');
        $evolucaoGrafico[$m] = ['gastos' => 0, 'label' => substr($mesesPt[$mesNum], 0, 3)];
    }
    foreach ($todasTransacoes as $t) {
        if ($t['tipo'] === 'saida') {
            $mStart = substr($t['data'], 0, 7);
            $mEnd = !empty($t['data_termino']) ? substr($t['data_termino'], 0, 7) : $mStart;
            
            foreach ($evolucaoGrafico as $mk => &$item) {
                if ($mk >= $mStart && $mk <= $mEnd) {
                    $item['gastos'] += $t['valor'];
                }
            }
            unset($item);
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
        if ($t['tipo'] === 'saida') {
            $dStart = $t['data'];
            $dEnd = !empty($t['data_termino']) ? $t['data_termino'] : $dStart;
            
            $diaTransacao = substr($dStart, 8, 2);
            $dataNoMesFiltro = $mesFiltro . '-' . $diaTransacao;
            
            if ($dataNoMesFiltro >= $dStart && $dataNoMesFiltro <= $dEnd) {
                if (isset($evolucaoGrafico[$dataNoMesFiltro])) {
                    $evolucaoGrafico[$dataNoMesFiltro]['gastos'] += $t['valor'];
                }
            }
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
    <script>
        (function(){var t=localStorage.getItem('cf-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');})();
    </script>
</head>
<body>
    <header class="top-bar">
        <div class="top-bar__inner">
            <div class="top-bar__user" style="display:flex; gap:1.5rem; align-items: center;">
                <div style="display:flex; align-items: center; gap:0.8rem;">
                    <img src="assets/img/logo.png" alt="Logo" style="width: 40px; height: 40px; object-fit: contain; border-radius: 50%; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" draggable="false">
                    <span class="top-bar__name">Olá, <?= htmlspecialchars($userNome) ?></span>
                </div>
                <a href="index.php?route=logout" class="btn btn-outline btn-sm">Sair</a>
            </div>
            <div style="display:flex; gap:1rem; align-items: center;">
                <a href="#modalNovaDespesa" class="btn btn-danger">Despesa</a>
                <a href="#modalAdicionarSaldo" class="btn btn-success">Saldo</a>
                <a href="#modalDespesasFixas" class="btn btn-outline btn-sm" title="Despesas Fixas">🔄 Fixas</a>
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
            <div class="stat-card">
                <div class="stat-card__icon icon-amber">📊</div>
                <div class="stat-card__label">Média Mensal de Gastos</div>
                <div class="stat-card__value">R$ <?= number_format($mediaGastosMensais, 2, ',', '.') ?></div>
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

                <form method="GET" action="index.php" style="display: flex; flex-direction: column; gap: 1rem;" class="mb-4">
                    <input type="hidden" name="route" value="dashboard">
                    
                    <!-- Barra de Pesquisa -->
                    <div style="display: flex; width: 100%;">
                        <input type="text" name="busca" placeholder="🔍 Buscar por nome da transação..." value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>" style="flex: 1; padding: 0.75rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text); font-family: inherit; font-size: 0.95rem;">
                    </div>

                    <div class="filter-bar">
                        <div class="filter-select-wrapper">
                            <select name="mes" class="filter-btn">
                            <option value="todos" <?= $mesFiltro === 'todos' ? 'selected' : '' ?>>Todos os Meses</option>
                            <?php 
                            $filterBaseDate = new DateTime('first day of this month');
                            for ($i = 0; $i < 12; $i++): 
                                $filterDt = clone $filterBaseDate;
                                $filterDt->modify("-{$i} months");
                                $m = $filterDt->format('Y-m');
                                $mesStr = $mesesPt[$filterDt->format('m')];
                                $anoStr = $filterDt->format('Y');
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
                    <div class="filter-select-wrapper">
                        <select name="categoria" class="filter-btn">
                            <option value="todas" <?= $categoriaFiltro === 'todas' ? 'selected' : '' ?>>Todas Categorias</option>
                            <option value="🛒" <?= $categoriaFiltro === '🛒' ? 'selected' : '' ?>>🛒 Mercado/Comida</option>
                            <option value="⚡" <?= $categoriaFiltro === '⚡' ? 'selected' : '' ?>>⚡ Energia</option>
                            <option value="💧" <?= $categoriaFiltro === '💧' ? 'selected' : '' ?>>💧 Água</option>
                            <option value="📶" <?= $categoriaFiltro === '📶' ? 'selected' : '' ?>>📶 Internet/Tel</option>
                            <option value="🚗" <?= $categoriaFiltro === '🚗' ? 'selected' : '' ?>>🚗 Transporte</option>
                            <option value="💊" <?= $categoriaFiltro === '💊' ? 'selected' : '' ?>>💊 Saúde</option>
                            <option value="🍿" <?= $categoriaFiltro === '🍿' ? 'selected' : '' ?>>🍿 Lazer</option>
                            <option value="🏠" <?= $categoriaFiltro === '🏠' ? 'selected' : '' ?>>🏠 Moradia</option>
                            <option value="🛍️" <?= $categoriaFiltro === '🛍️' ? 'selected' : '' ?>>🛍️ Compras</option>
                            <option value="📄" <?= $categoriaFiltro === '📄' ? 'selected' : '' ?>>📄 Outros</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                    </div>
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
                                    <span class="expense-item__date">
                                        <?= date('d M, Y', strtotime($despesa['data'])) ?>
                                        <?php if (!empty($despesa['data_termino'])): ?>
                                            <span style="opacity: 0.6; font-size: 0.85em; margin-left: 4px;">➔ até <?= date('d M, Y', strtotime($despesa['data_termino'])) ?></span>
                                        <?php endif; ?>
                                    </span>
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
                    <input id="new_nome" name="nome" type="text" required placeholder="Ex: Mercado" maxlength="40">
                    <label for="new_nome">Título / Nome</label>
                </div>

                <div class="form-floating">
                    <input id="new_descricao" name="descricao" type="text" placeholder="Ex: Compras do mês" maxlength="150">
                    <label for="new_descricao">Descrição (opcional)</label>
                </div>
                
                <div class="form-floating">
                    <input id="new_valor" name="valor" type="text" required placeholder="Ex: 89.90" maxlength="10">
                    <label for="new_valor">Valor (R$)</label>
                </div>
                
                <div class="form-floating" style="display: flex; gap: 1rem;">
                    <div style="flex: 1; position: relative;">
                        <input id="new_data" name="data" type="date" required value="<?= date('Y-m-d') ?>" style="width: 100%; padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text);">
                        <label for="new_data" style="position: absolute; top: -20px; left: 0; font-size: 0.8rem; color: var(--color-text-light);">Data Inicial</label>
                    </div>
                    <div style="flex: 1; position: relative;">
                        <input id="new_data_termino" name="data_termino" type="date" style="width: 100%; padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text);">
                        <label for="new_data_termino" style="position: absolute; top: -20px; left: 0; font-size: 0.8rem; color: var(--color-text-light);">Data Final (Recorrência)</label>
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
                    <input id="saldo_nome" name="nome" type="text" required placeholder="Ex: Salário" maxlength="40">
                    <label for="saldo_nome">Título / Nome</label>
                </div>

                <div class="form-floating">
                    <input id="saldo_descricao" name="descricao" type="text" placeholder="Ex: Salário de Janeiro" maxlength="150">
                    <label for="saldo_descricao">Descrição (opcional)</label>
                </div>
                
                <div class="form-floating">
                    <input id="saldo_valor" name="valor" type="text" required placeholder="Ex: 1500.00" maxlength="10">
                    <label for="saldo_valor">Valor (R$)</label>
                </div>
                
                <div class="form-floating" style="display: flex; gap: 1rem;">
                    <div style="flex: 1; position: relative;">
                        <input id="saldo_data" name="data" type="date" required value="<?= date('Y-m-d') ?>" style="width: 100%; padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text);">
                        <label for="saldo_data" style="position: absolute; top: -20px; left: 0; font-size: 0.8rem; color: var(--color-text-light);">Data Inicial</label>
                    </div>
                    <div style="flex: 1; position: relative;">
                        <input id="saldo_data_termino" name="data_termino" type="date" style="width: 100%; padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text);">
                        <label for="saldo_data_termino" style="position: absolute; top: -20px; left: 0; font-size: 0.8rem; color: var(--color-text-light);">Data Final (Recorrência)</label>
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


    <!-- Modal: Gerenciar Recorrentes Fixos -->
    <div id="modalDespesasFixas" class="modal-overlay">
        <div class="modal-content" style="max-width: 650px;">
            <a href="#!" class="modal-close">✕</a>
            <h2>🔄 Recorrentes Fixos</h2>
            <p class="text-sm mb-4" style="color: var(--color-text-light);">
                Despesas e saldos que se repetem todo mês automaticamente.
            </p>

            <!-- Botão para abrir modal de novo recorrente -->
            <div style="margin-bottom: 1.5rem; display: flex; gap: 0.75rem;">
                <a href="#modalNovaRecorrente" class="btn btn-primary btn-sm">➕ Novo Recorrente</a>
            </div>

            <div class="expense-list" style="max-height: 50vh; overflow-y: auto;">
                <?php if (empty($despesasRecorrentes)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🔄</div>
                        <div class="empty-title">Nenhum Recorrente</div>
                        <div class="empty-desc">Adicione despesas ou saldos que se repetem todo mês, como aluguel, salário ou internet.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($despesasRecorrentes as $rec): 
                        $tipoRec = $rec['tipo'] ?? 'saida';
                        $isEntrada = $tipoRec === 'entrada';
                    ?>
                        <div class="expense-item <?= $rec['ativo'] ? '' : 'expense-item--deleted' ?>">
                            <div class="expense-item__info">
                                <div class="expense-item__icon"><?= $rec['icone'] ?></div>
                                <div class="expense-item__details">
                                    <div class="expense-item__name">
                                        <?= htmlspecialchars($rec['nome']) ?>
                                        <?php if ($isEntrada): ?>
                                            <span class="badge badge-entrada">Saldo</span>
                                        <?php else: ?>
                                            <span class="badge badge-alta">Despesa</span>
                                        <?php endif; ?>
                                        <?php if (!$rec['ativo']): ?>
                                            <span class="badge badge-media">Pausada</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($rec['descricao'])): ?>
                                        <div style="font-size: 0.8rem; color: var(--color-text-light);"><?= htmlspecialchars($rec['descricao']) ?></div>
                                    <?php endif; ?>
                                    <span class="expense-item__date">Dia <?= $rec['dia_vencimento'] ?> de cada mês <?php if (!empty($rec['data_inicio'])): ?>— Desde <?= date('m/Y', strtotime($rec['data_inicio'])) ?><?php endif; ?></span>
                                </div>
                            </div>
                            <div class="expense-item__actions">
                                <?php if ($isEntrada): ?>
                                    <div class="expense-item__value" style="color: var(--color-success);">+ R$ <?= number_format($rec['valor'], 2, ',', '.') ?></div>
                                <?php else: ?>
                                    <div class="expense-item__value expense-item__value--expense">- R$ <?= number_format($rec['valor'], 2, ',', '.') ?></div>
                                <?php endif; ?>
                                <div class="expense-actions-btns">
                                    <?php if ($rec['ativo']): ?>
                                        <form method="post" action="index.php?route=dashboard" style="margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="desativar_recorrente">
                                            <input type="hidden" name="recorrente_id" value="<?= htmlspecialchars($rec['id']) ?>">
                                            <button type="submit" class="btn-icon" title="Pausar">⏸️</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="index.php?route=dashboard" style="margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="reativar_recorrente">
                                            <input type="hidden" name="recorrente_id" value="<?= htmlspecialchars($rec['id']) ?>">
                                            <button type="submit" class="btn-icon" title="Reativar" style="background: var(--color-success-bg); color: var(--color-success);">▶️</button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="#confirmarRemoverFixo_<?= htmlspecialchars($rec['id']) ?>" class="btn-icon danger" title="Remover">🗑️</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modais de confirmação de remoção de fixos -->
    <?php if (!empty($despesasRecorrentes)): ?>
        <?php foreach ($despesasRecorrentes as $rec): ?>
            <div id="confirmarRemoverFixo_<?= htmlspecialchars($rec['id']) ?>" class="modal-overlay">
                <div class="modal-content" style="max-width: 500px; text-align: center;">
                    <a href="#modalDespesasFixas" class="modal-close">✕</a>
                    <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                    <h2 style="font-size: 1.3rem; margin-bottom: 0.5rem;">Remover Fixo</h2>
                    <p style="color: var(--color-text-light); margin-bottom: 0.5rem;">
                        Deseja remover "<strong><?= htmlspecialchars($rec['nome']) ?></strong>"?
                    </p>
                    <p style="color: var(--color-text-light); margin-bottom: 2rem; font-size: 0.9rem;">
                        Escolha o que fazer com as transações já lançadas nos meses anteriores:
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem; align-items: stretch;">
                        <!-- Opção 1: Manter histórico -->
                        <form method="post" action="index.php?route=dashboard" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="action" value="remover_recorrente">
                            <input type="hidden" name="recorrente_id" value="<?= htmlspecialchars($rec['id']) ?>">
                            <button type="submit" class="btn btn-primary btn-block" style="padding: 0.85rem; font-size: 0.95rem;">
                                📋 Só parar de repetir<br>
                                <small style="opacity: 0.8; font-weight: 400;">Mantém as transações já lançadas</small>
                            </button>
                        </form>
                        <!-- Opção 2: Apagar tudo -->
                        <form method="post" action="index.php?route=dashboard" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="action" value="remover_recorrente_completo">
                            <input type="hidden" name="recorrente_id" value="<?= htmlspecialchars($rec['id']) ?>">
                            <button type="submit" class="btn btn-danger btn-block" style="padding: 0.85rem; font-size: 0.95rem;">
                                🗑️ Apagar tudo<br>
                                <small style="opacity: 0.8; font-weight: 400;">Remove o fixo e todas as transações geradas</small>
                            </button>
                        </form>
                        <!-- Cancelar -->
                        <a href="#modalDespesasFixas" class="btn btn-outline" style="padding: 0.75rem;">Cancelar</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Modal: Criar Novo Recorrente (Despesa ou Saldo) -->
    <div id="modalNovaRecorrente" class="modal-overlay">
        <div class="modal-content">
            <a href="#modalDespesasFixas" class="modal-close">✕</a>
            <h2>🔄 Novo Recorrente Fixo</h2>
            <form method="post" action="index.php?route=dashboard" style="margin-top:1.5rem;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="salvar_recorrente">

                <!-- Seletor de Tipo: Despesa ou Saldo -->
                <div style="display: flex; gap: 0; margin-bottom: 1.5rem; border-radius: var(--radius-full); overflow: hidden; border: 2px solid var(--color-border);">
                    <label style="flex: 1; cursor: pointer;">
                        <input type="radio" name="tipo_recorrente" value="saida" checked style="display: none;">
                        <div class="tipo-toggle tipo-toggle--despesa" style="padding: 0.75rem; text-align: center; font-weight: 700; font-size: 0.95rem; transition: all 0.2s; background: var(--color-danger); color: white;">
                            📉 Despesa Fixa
                        </div>
                    </label>
                    <label style="flex: 1; cursor: pointer;">
                        <input type="radio" name="tipo_recorrente" value="entrada" style="display: none;">
                        <div class="tipo-toggle tipo-toggle--saldo" style="padding: 0.75rem; text-align: center; font-weight: 700; font-size: 0.95rem; transition: all 0.2s; background: var(--color-surface-hover); color: var(--color-text-light);">
                            📈 Saldo Fixo
                        </div>
                    </label>
                </div>

                <div class="form-floating">
                    <input id="rec_nome" name="nome" type="text" required placeholder="Ex: Aluguel" maxlength="40">
                    <label for="rec_nome">Título / Nome</label>
                </div>

                <div class="form-floating">
                    <input id="rec_descricao" name="descricao" type="text" placeholder="Ex: Aluguel do apartamento" maxlength="150">
                    <label for="rec_descricao">Descrição (opcional)</label>
                </div>

                <div class="form-floating">
                    <input id="rec_valor" name="valor" type="text" required placeholder="Ex: 1200.00" maxlength="10">
                    <label for="rec_valor">Valor Mensal (R$)</label>
                </div>

                <div class="form-floating">
                    <select id="rec_dia" name="dia_vencimento" style="width: 100%; padding: 1.75rem 1.25rem 0.5rem; font-size: 1.05rem; font-weight: 500; border: 1px solid var(--color-border); border-radius: var(--radius-md); background: var(--color-surface);">
                        <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?= $d ?>" <?= $d === (int)date('d') ? 'selected' : '' ?>>Dia <?= $d ?></option>
                        <?php endfor; ?>
                    </select>
                    <label for="rec_dia" style="top: 0.6rem; transform: translateY(0); font-size: 0.75rem; font-weight: 600; color: var(--color-primary);">Dia do Mês</label>
                </div>

                <div class="form-floating">
                    <input id="rec_data_inicio" name="data_inicio" type="date" value="<?= date('Y-m-d') ?>">
                    <label for="rec_data_inicio">Primeiro lançamento</label>
                </div>

                <div class="form-floating" style="margin-top: 1.5rem;">
                    <select id="rec_icone" name="icone" style="width: 100%; padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text);">
                        <optgroup label="— Despesas —">
                            <option value="🔄">🔄 Recorrente (Padrão)</option>
                            <option value="🏠">🏠 Aluguel / Moradia</option>
                            <option value="📶">📶 Internet / Telefone</option>
                            <option value="⚡">⚡ Energia / Luz</option>
                            <option value="💧">💧 Água</option>
                            <option value="📺">📺 Streaming / Assinatura</option>
                            <option value="🚗">🚗 Seguro / Veículo</option>
                            <option value="🏫">🏫 Escola / Faculdade</option>
                            <option value="🏋️">🏋️ Academia</option>
                            <option value="💳">💳 Cartão / Parcela</option>
                        </optgroup>
                        <optgroup label="— Saldos —">
                            <option value="💵">💵 Dinheiro</option>
                            <option value="💼">💼 Salário</option>
                            <option value="📈">📈 Rendimento / Investimento</option>
                            <option value="🏦">🏦 Transferência</option>
                            <option value="🤑">🤑 Bônus / Freelance</option>
                            <option value="🎁">🎁 Presente / Doação</option>
                        </optgroup>
                    </select>
                    <label for="rec_icone" style="font-size: 0.8rem; color: var(--color-text-light); top: -20px; left: 0;">Símbolo (Ícone)</label>
                </div>

                <button type="submit" class="btn btn-primary btn-block mt-4" style="padding: 1rem; font-size:1.05rem;">Criar Recorrente Fixo</button>
            </form>
        </div>
    </div>

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
                        <input id="edit_nome_<?= htmlspecialchars($despesa['id']) ?>" name="nome" type="text" required value="<?= htmlspecialchars($despesa['nome']) ?>" placeholder="Ex: Mercado" maxlength="40">
                        <label for="edit_nome_<?= htmlspecialchars($despesa['id']) ?>">Título / Nome</label>
                    </div>

                    <div class="form-floating">
                        <input id="edit_descricao_<?= htmlspecialchars($despesa['id']) ?>" name="descricao" type="text" value="<?= htmlspecialchars($despesa['descricao'] ?? '') ?>" placeholder="Ex: Detalhes da compra" maxlength="150">
                        <label for="edit_descricao_<?= htmlspecialchars($despesa['id']) ?>">Descrição (opcional)</label>
                    </div>
                    
                    <div class="form-floating">
                        <input id="edit_valor_<?= htmlspecialchars($despesa['id']) ?>" name="valor" type="text" required value="<?= htmlspecialchars($despesa['valor']) ?>" placeholder="Ex: 89.90" maxlength="10">
                        <label for="edit_valor_<?= htmlspecialchars($despesa['id']) ?>">Valor (R$)</label>
                    </div>
                    
                    <div class="form-floating" style="display: flex; gap: 1rem;">
                        <div style="flex: 1; position: relative;">
                            <input id="edit_data_<?= htmlspecialchars($despesa['id']) ?>" name="data" type="date" required value="<?= htmlspecialchars(substr($despesa['data'], 0, 10)) ?>" style="width: 100%; padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text);">
                            <label for="edit_data_<?= htmlspecialchars($despesa['id']) ?>" style="position: absolute; top: -20px; left: 0; font-size: 0.8rem; color: var(--color-text-light);">Data Inicial</label>
                        </div>
                        <div style="flex: 1; position: relative;">
                            <input id="edit_data_termino_<?= htmlspecialchars($despesa['id']) ?>" name="data_termino" type="date" value="<?= htmlspecialchars(!empty($despesa['data_termino']) ? substr($despesa['data_termino'], 0, 10) : '') ?>" style="width: 100%; padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text);">
                            <label for="edit_data_termino_<?= htmlspecialchars($despesa['id']) ?>" style="position: absolute; top: -20px; left: 0; font-size: 0.8rem; color: var(--color-text-light);">Data Final (Recorrência)</label>
                        </div>
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



    <!-- Dark Mode Toggle -->
    <button class="theme-toggle" id="themeToggleBtn" title="Alternar Modo Escuro" aria-label="Alternar modo escuro">
        <span class="icon-sun">☀️</span>
        <span class="icon-moon">🌙</span>
    </button>

    <script>
        (function() {
            var btn = document.getElementById('themeToggleBtn');
            btn.addEventListener('click', function() {
                var html = document.documentElement;
                var isDark = html.getAttribute('data-theme') === 'dark';
                if (isDark) {
                    html.removeAttribute('data-theme');
                    localStorage.setItem('cf-theme', 'light');
                } else {
                    html.setAttribute('data-theme', 'dark');
                    localStorage.setItem('cf-theme', 'dark');
                }
            });
        })();
    </script>

</body>
</html>