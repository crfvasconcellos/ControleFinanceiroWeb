<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle Financeiro - Editar Despesa</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="top-bar">
        <div class="top-bar__inner">
            <div class="top-bar__name">Controle Financeiro</div>
            <a href="index.php" class="btn btn-outline text-sm">Voltar ao Dashboard</a>
        </div>
    </header>

    <main class="container" style="max-width: 500px;">
        <section class="card">
            <h2>Editar Transação</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" novalidate style="margin-top: 2rem;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div class="form-floating">
                    <input id="edit_nome" name="nome" type="text" required value="<?= htmlspecialchars($data['nome'] ?? '') ?>" placeholder="Ex: Mercado" maxlength="140">
                    <label for="edit_nome">Título / Nome</label>
                </div>

                <div class="form-floating">
                    <input id="edit_descricao" name="descricao" type="text" value="<?= htmlspecialchars($data['descricao'] ?? '') ?>" placeholder="Ex: Detalhes da compra" maxlength="200">
                    <label for="edit_descricao">Descrição (opcional)</label>
                </div>

                <div class="form-floating">
                    <input id="edit_valor" name="valor" type="text" required value="<?= htmlspecialchars($data['valor'] ?? '') ?>" placeholder="Ex: 89.90">
                    <label for="edit_valor">Valor (R$)</label>
                </div>

                <div class="form-floating">
                    <input id="edit_data" name="data" type="date" required value="<?= htmlspecialchars($data['data'] ?? '') ?>">
                    <label for="edit_data">Data da Transação</label>
                </div>

                <div class="form-floating" style="margin-top: 1.5rem;">
                    <select id="edit_icone" name="icone" style="width: 100%; padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text);">
                        <option value="<?= htmlspecialchars($data['icone']) ?>" selected>Símbolo Atual: <?= htmlspecialchars($data['icone']) ?></option>
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
                    <label for="edit_icone" style="font-size: 0.8rem; color: var(--color-text-light); top: -20px; left: 0;">Alterar Símbolo (Ícone)</label>
                </div>

                <div class="form-floating" style="margin-top: 1.5rem;">
                    <input id="edit_comprovante" name="comprovante" type="file" accept=".pdf" style="padding-top: 1.5rem;">
                    <label for="edit_comprovante" style="top: -5px; font-size: 0.85rem;">Novo Comprovante (PDF opcional)</label>
                    <?php if (!empty($data['comprovante'])): ?>
                        <div style="font-size: 0.8rem; margin-top: 0.5rem;">
                            Comprovante atual: <a href="<?= htmlspecialchars($data['comprovante']) ?>" target="_blank">Ver PDF</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 1rem;">Salvar Alterações</button>
                    <a href="index.php" class="btn btn-outline" style="flex: 1; padding: 1rem;">Cancelar</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
