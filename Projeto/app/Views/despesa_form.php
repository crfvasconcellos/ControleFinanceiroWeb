<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle Financeiro - Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="top-bar">
        <div class="top-bar__inner">
            <div class="top-bar__user">
                <?php $inicial = strtoupper($userNome[0] ?? '?'); ?>
                <span class="top-bar__avatar"><?= htmlspecialchars($inicial) ?></span>
                <span class="top-bar__name">Olá, <?= htmlspecialchars($userNome) ?></span>
            </div>
            <a href="index.php?route=logout" class="top-bar__logout" id="btnLogout">Sair</a>
        </div>
    </header>

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
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
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

                        <div class="total-card">
                            <div class="total-card__header">
                                <span class="total-card__icon">💰</span>
                                <span class="total-card__label">Total de Gastos</span>
                            </div>
                            <div class="total-card__amount">
                                R$ <?= number_format($totalDespesas, 2, ',', '.') ?>
                            </div>
                            <div class="total-card__count">
                                <?= count($listaDespesas) ?> despesa<?= count($listaDespesas) !== 1 ? 's' : '' ?> registrada<?= count($listaDespesas) !== 1 ? 's' : '' ?>
                            </div>
                        </div>

                        <?php foreach ($listaDespesas as $despesa): ?>
                            <div class="expense-item">
                                <div class="expense-item__info">
                                    <strong class="expense-item__name">
                                        <?= htmlspecialchars($despesa['nome']) ?>
                                    </strong>
                                    <small class="expense-item__date">
                                        <?= date('d/m/Y', strtotime($despesa['data'])) ?>
                                    </small>
                                </div>
                                <div class="expense-item__actions">
                                    <div class="expense-item__value">
                                        R$ <?= number_format($despesa['valor'], 2, ',', '.') ?>
                                    </div>
                                    <form method="post" onsubmit="return confirm('Deseja remover esta despesa?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="action" value="remover">
                                        <input type="hidden" name="despesa_id" value="<?= htmlspecialchars($despesa['id']) ?>">
                                        <button type="submit" class="btn-remove">Remover</button>
                                    </form>
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