<?php

namespace App\Middleware;

class Auth {

    /**
     * Verifica se o usuário está autenticado.
     * Redireciona para o login se não estiver.
     */
    public static function verificar(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit;
        }
    }

    /**
     * Verifica se o usuário já está logado.
     * Redireciona para o dashboard se estiver (evita acessar login/registro logado).
     */
    public static function redirecSeLogado(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['user_id'])) {
            header('Location: index.php?route=dashboard');
            exit;
        }
    }
}
