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
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <label for="nome">Nome da despesa</label>
                <input
                    id="nome"
                    name="nome"
                    type="text"
                    required
                    value="<?= htmlspecialchars($data['nome'] ?? '') ?>"
                    placeholder="Ex: Mercado"
                >

                <label for="valor">Valor</label>
                <input
                    id="valor"
                    name="valor"
                    type="text"
                    required
                    value="<?= htmlspecialchars($data['valor'] ?? '') ?>"
                    placeholder="Ex: 89.90"
                >

                <label for="data">Data</label>
                <input
                    id="data"
                    name="data"
                    type="date"
                    required
                    value="<?= htmlspecialchars($data['data'] ?? '') ?>"
                >

                <button type="submit">Salvar Despesa</button>
            </form>

            <?php if (!empty($listaDespesas)): ?>
                <button type="button" id="btnAbrir" class="btn-secondary" style="width: 100%;">
                    Visualizar Despesas
                </button>

                <div class="modal-overlay" id="modalLista">
                    <div class="modal-content">
                        <button type="button" id="btnFechar" class="close-modal">✕</button>
                        
                        <h2>Despesas Registradas</h2>
                        
                        <?php foreach ($listaDespesas as $despesa): ?>
                            <div class="expense-item">
                                <div>
                                    <strong style="text-transform: capitalize;">
                                        <?= htmlspecialchars($despesa['nome']) ?>
                                    </strong>
                                    <small style="display: block; color: #64748b; margin-top: 4px;">
                                        <?= date('d/m/Y', strtotime($despesa['data'])) ?>
                                    </small>
                                </div>
                                <div style="font-weight: 700; color: #2563eb;">
                                    R$ <?= number_format($despesa['valor'], 2, ',', '.') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </section>
    </main>

    <script>
        const modal = document.getElementById('modalLista');
        const btnAbrir = document.getElementById('btnAbrir');
        const btnFechar = document.getElementById('btnFechar');

        if (btnAbrir) {
            btnAbrir.addEventListener('click', () => {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        }

        if (btnFechar) {
            btnFechar.addEventListener('click', () => {
                modal.classList.remove('active');
                document.body.style.overflow = 'auto'; 
            });
        }

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });
    </script>
</body>
</html>