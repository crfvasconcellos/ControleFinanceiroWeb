<?php

namespace App\Middleware;

use App\Models\Usuario;

class Auth {

    /**
     * Verifica se o usuário está autenticado.
     * Redireciona para o login se não estiver.
     * Também valida se o usuário ainda existe no banco de dados.
     */
    public static function verificar(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit;
        }

        // Verifica se o usuário ainda existe no banco de dados
        $model = new Usuario();
        if (!$model->existePorId($_SESSION['user_id'])) {
            // Usuário foi removido do banco — limpa a sessão e redireciona
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']);
            }
            session_destroy();
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
