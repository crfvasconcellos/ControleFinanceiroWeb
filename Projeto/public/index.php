<?php
session_start();

if (!isset($_SESSION['despesas'])) {
    $_SESSION['despesas'] = [];
}

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $valor = $_POST['valor'] ?? '';
    $data = $_POST['data'] ?? '';

    if (!empty($nome) && !empty($valor) && !empty($data)) {
        $novaDespesa = [
            'nome' => htmlspecialchars($nome),
            'valor' => floatval($valor),
            'data' => htmlspecialchars($data)
        ];

        $_SESSION['despesas'][] = $novaDespesa;
        $mensagem = "Despesa adicionada com sucesso!";
        $tipoMensagem = 'sucesso';
    } else {
        $mensagem = "Por favor, preencha todos os campos.";
        $tipoMensagem = 'erro';
    }
}

function formatarMoeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function formatarData(string $data): string
{
    $dataFormatada = DateTime::createFromFormat('Y-m-d', $data);

    if ($dataFormatada === false) {
        return htmlspecialchars($data);
    }

    return $dataFormatada->format('d/m/Y');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Despesa - Controle Financeiro</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Controle Financeiro</h2>
        <p class="subtitulo">Cadastre despesas e acompanhe a lista atualizada automaticamente.</p>
        
        <?php if ($mensagem): ?>
            <div class="mensagem <?php echo $tipoMensagem; ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="form-group">
                <label for="nome">Nome da Despesa:</label>
                <input type="text" id="nome" name="nome" required placeholder="Ex: Conta de Luz">
            </div>
            
            <div class="form-group">
                <label for="valor">Valor (R$):</label>
                <input type="number" id="valor" name="valor" step="0.01" required placeholder="0.00">
            </div>
            
            <div class="form-group">
                <label for="data">Data:</label>
                <input type="date" id="data" name="data" required>
            </div>
            
            <button type="submit">Adicionar Despesa</button>
        </form>

        <section class="lista-despesas">
            <div class="lista-cabecalho">
                <h3>Despesas registradas</h3>
                <span class="contador"><?php echo count($_SESSION['despesas']); ?> item(ns)</span>
            </div>

            <?php if (!empty($_SESSION['despesas'])): ?>
                <ul class="lista-itens">
                    <?php foreach (array_reverse($_SESSION['despesas']) as $despesa): ?>
                        <li class="item-despesa">
                            <div>
                                <strong><?php echo htmlspecialchars($despesa['nome']); ?></strong>
                                <span><?php echo formatarData($despesa['data']); ?></span>
                            </div>
                            <span class="valor-despesa"><?php echo formatarMoeda((float) $despesa['valor']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="estado-vazio">Nenhuma despesa cadastrada ainda.</p>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>