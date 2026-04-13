<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle Financeiro - Adicionar Despesa</title>
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
                    value="<?= htmlspecialchars($data['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Ex: Mercado"
                >

                <label for="valor">Valor</label>
                <input
                    id="valor"
                    name="valor"
                    type="text"
                    inputmode="decimal"
                    required
                    value="<?= htmlspecialchars($data['valor'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Ex: 89.90"
                >

                <label for="data">Data</label>
                <input
                    id="data"
                    name="data"
                    type="date"
                    required
                    value="<?= htmlspecialchars($data['data'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                >

                <button type="submit">Salvar Despesa</button>
            </form>
        </section>
    </main>
</body>
</html>