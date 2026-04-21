<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle Financeiro - Criar Conta</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="container">
        <section class="card auth-card">
            <div class="auth-header">
                <div class="auth-icon">📝</div>
                <h1>Criar Conta</h1>
                <p class="subtitle">Preencha os dados para se cadastrar</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <label for="nome">Nome</label>
                <input
                    id="nome"
                    name="nome"
                    type="text"
                    required
                    autocomplete="name"
                    value="<?= htmlspecialchars($data['nome'] ?? '') ?>"
                    placeholder="Seu nome completo"
                >

                <label for="email">E-mail</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    autocomplete="email"
                    value="<?= htmlspecialchars($data['email'] ?? '') ?>"
                    placeholder="seu@email.com"
                >

                <label for="senha">Senha</label>
                <input
                    id="senha"
                    name="senha"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="Mínimo 6 caracteres"
                >

                <label for="confirmar_senha">Confirmar Senha</label>
                <input
                    id="confirmar_senha"
                    name="confirmar_senha"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="Repita a senha"
                >

                <button type="submit" id="btnRegistro">Criar Conta</button>
            </form>

            <div class="auth-footer">
                <p>Já tem uma conta? <a href="index.php?route=login">Entrar</a></p>
            </div>
        </section>
    </main>
</body>
</html>
