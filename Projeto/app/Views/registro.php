<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle Financeiro - Criar Conta</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="body-auth">
    <div class="auth-card-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon">✨</div>
                <h1>Criar Conta</h1>
                <p>Preencha os dados para se cadastrar</p>
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

                <div class="form-floating">
                    <input id="nome" name="nome" type="text" required autocomplete="name" value="<?= htmlspecialchars($data['nome'] ?? '') ?>" placeholder="Seu nome completo">
                    <label for="nome">Nome Completo</label>
                </div>

                <div class="form-floating">
                    <input id="email" name="email" type="email" required autocomplete="email" value="<?= htmlspecialchars($data['email'] ?? '') ?>" placeholder="E-mail">
                    <label for="email">E-mail</label>
                </div>

                <div class="form-floating">
                    <input id="senha" name="senha" type="password" required autocomplete="new-password" placeholder="Senha">
                    <label for="senha">Senha</label>
                </div>

                <div class="form-floating">
                    <input id="confirmar_senha" name="confirmar_senha" type="password" required autocomplete="new-password" placeholder="Repita a senha">
                    <label for="confirmar_senha">Confirmar Senha</label>
                </div>

                <button type="submit" class="btn btn-primary btn-block mt-4" style="padding: 1.25rem; font-size: 1.1rem; border-radius: var(--radius-md);">Criar Conta</button>
            </form>

            <div class="text-center mt-4 text-sm" style="padding-top: 1.5rem; border-top: 1px solid rgba(0,0,0,0.05);">
                Já tem uma conta? <a href="index.php?route=login" class="font-bold">Entrar</a>
            </div>
        </div>
    </div>
</body>
</html>
