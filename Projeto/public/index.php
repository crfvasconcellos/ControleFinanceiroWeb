<?php
$storageDir = __DIR__ . '/../data';
$storageFile = $storageDir . '/despesas.json';

if (!is_dir($storageDir)) {
    mkdir($storageDir, 0777, true);
}

if (!file_exists($storageFile)) {
    file_put_contents($storageFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$errors = [];
$successMessage = '';

$nome = '';
$valor = '';
$data = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $valor = str_replace(',', '.', trim($_POST['valor'] ?? ''));
    $data = trim($_POST['data'] ?? '');

    if ($nome === '') {
        $errors[] = 'Informe o nome da despesa.';
    }

    if ($valor === '' || !is_numeric($valor) || (float) $valor <= 0) {
        $errors[] = 'Informe um valor valido maior que zero.';
    }

    $dataObj = DateTime::createFromFormat('Y-m-d', $data);
    $dataValida = $dataObj && $dataObj->format('Y-m-d') === $data;
    if (!$dataValida) {
        $errors[] = 'Informe uma data valida.';
    }

    if (empty($errors)) {
        $despesas = json_decode(file_get_contents($storageFile), true);
        if (!is_array($despesas)) {
            $despesas = [];
        }

        $despesas[] = [
            'id' => uniqid('desp_', true),
            'nome' => $nome,
            'valor' => round((float) $valor, 2),
            'data' => $data,
            'criado_em' => date('c'),
        ];

        file_put_contents(
            $storageFile,
            json_encode($despesas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $successMessage = 'Despesa adicionada com sucesso.';
        $nome = '';
        $valor = '';
        $data = '';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>US01 - Adicionar Despesa</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="container">
        <section class="card">
            <h1>Adicionar Despesa</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($successMessage !== ''): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <label for="nome">Nome da despesa</label>
                <input
                    id="nome"
                    name="nome"
                    type="text"
                    maxlength="120"
                    required
                    value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Ex: Mercado"
                >

                <label for="valor">Valor</label>
                <input
                    id="valor"
                    name="valor"
                    type="text"
                    inputmode="decimal"
                    required
                    value="<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Ex: 89.90"
                >

                <label for="data">Data</label>
                <input
                    id="data"
                    name="data"
                    type="date"
                    required
                    value="<?= htmlspecialchars($data, ENT_QUOTES, 'UTF-8') ?>"
                >

                <button type="submit">Salvar Despesa</button>
            </form>
        </section>
    </main>
</body>
</html>