<?php
session_start();

if (!isset($_SESSION['despesas'])) {
    $_SESSION['despesas'] = [];
}

$mensagem = '';

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
    } else {
        $mensagem = "Por favor, preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Despesa - Controle Financeiro</title>
    <link rel="stylesheet" href="/Projeto/assets/style.css">
</head>
<body>
    <div class="container">
        <h2>Adicionar Nova Despesa</h2>
        
        <?php if ($mensagem): ?>
            <div class="mensagem <?php echo strpos($mensagem, 'sucesso') !== false ? 'sucesso' : 'erro'; ?>">
                <?php echo $mensagem; ?>
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
    </div>
</body>
</html>