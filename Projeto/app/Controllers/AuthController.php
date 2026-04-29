<?php

namespace App\Controllers;

use App\Models\Usuario;
use App\Middleware\Auth;

class AuthController {

    /**
     * Exibe e processa o formulário de login.
     */
    public function login() {
        Auth::redirecSeLogado();

        $errors = [];
        $successMessage = '';
        $data = ['email' => ''];

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrfToken = $_SESSION['csrf_token'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tokenRecebido = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $tokenRecebido)) {
                $errors[] = 'Token de segurança inválido. Recarregue a página.';
            }

            if (empty($errors)) {
                $data['email'] = trim($_POST['email'] ?? '');
                $senha = $_POST['senha'] ?? '';

                if ($data['email'] === '') {
                    $errors[] = 'Informe seu e-mail.';
                }

                if ($senha === '') {
                    $errors[] = 'Informe sua senha.';
                }

                if (empty($errors)) {
                    $model = new Usuario();
                    $usuario = $model->autenticar($data['email'], $senha);

                    if ($usuario) {
                        // Regenera sessão por segurança
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $usuario['id'];
                        $_SESSION['user_nome'] = $usuario['nome'];
                        $_SESSION['user_email'] = $usuario['email'];
                        unset($_SESSION['csrf_token']);

                        header('Location: index.php?route=dashboard');
                        exit;
                    } else {
                        $errors[] = 'E-mail ou senha incorretos.';
                    }
                }
            }
        }

        // Mensagem de sucesso vinda do registro
        if (!empty($_SESSION['successMessage'])) {
            $successMessage = $_SESSION['successMessage'];
            unset($_SESSION['successMessage']);
        }

        require_once __DIR__ . '/../Views/login.php';
    }

    /**
     * Exibe e processa o formulário de registro.
     */
    public function registro() {
        Auth::redirecSeLogado();

        $errors = [];
        $data = ['nome' => '', 'email' => ''];

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrfToken = $_SESSION['csrf_token'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tokenRecebido = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $tokenRecebido)) {
                $errors[] = 'Token de segurança inválido. Recarregue a página.';
            }

            if (empty($errors)) {
                $data['nome'] = trim($_POST['nome'] ?? '');
                $data['email'] = trim($_POST['email'] ?? '');
                $senha = $_POST['senha'] ?? '';
                $confirmarSenha = $_POST['confirmar_senha'] ?? '';

                if ($data['nome'] === '') {
                    $errors[] = 'Informe seu nome.';
                }

                if ($data['email'] === '') {
                    $errors[] = 'Informe seu e-mail.';
                } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Informe um e-mail válido.';
                }

                if (strlen($senha) < 6) {
                    $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
                }

                if ($senha !== $confirmarSenha) {
                    $errors[] = 'As senhas não coincidem.';
                }

                if (empty($errors)) {
                    $model = new Usuario();
                    $usuario = $model->registrar($data['nome'], $data['email'], $senha);

                    if ($usuario) {
                        $_SESSION['successMessage'] = 'Conta criada com sucesso! Faça login.';
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        header('Location: index.php?route=login');
                        exit;
                    } else {
                        $errors[] = 'Este e-mail já está cadastrado.';
                    }
                }
            }
        }

        require_once __DIR__ . '/../Views/registro.php';
    }

    /**
     * Encerra a sessão e redireciona para o login.
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();

        header('Location: index.php?route=login');
        exit;
    }
}
