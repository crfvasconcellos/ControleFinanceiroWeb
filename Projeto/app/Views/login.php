<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle Financeiro - Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="container">
        <section class="card auth-card">
            <div class="auth-header">
                <div class="auth-icon">
                    <img src="assets/img/logo.png" alt="Logo Controle Financeiro" class="auth-logo" draggable="false" ondragstart="return false" onselectstart="return false">
                </div>
                <h1>Controle Financeiro</h1>
                <p class="subtitle">Acesse sua conta para gerenciar suas despesas</p>
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

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

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
                    autocomplete="current-password"
                    placeholder="••••••••"
                >

                <button type="submit" id="btnLogin">Entrar</button>
            </form>

            <div class="auth-footer">
                <p>Não tem uma conta? <a href="index.php?route=registro">Cadastre-se</a></p>
            </div>
        </section>
    </main>
</body>
</html>
