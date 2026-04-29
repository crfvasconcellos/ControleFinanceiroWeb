<?php

namespace App\Controllers;

use App\Models\Despesa;
use App\Middleware\Auth;

class DespesaController {
    public function create() {
        // Verifica autenticação
        Auth::verificar();

        $errors = [];
        $successMessage = '';
        $data = ['nome' => '', 'valor' => '', 'data' => ''];

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Gerar token CSRF se não existir
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrfToken = $_SESSION['csrf_token'];

        // Dados do usuário logado
        $userId = $_SESSION['user_id'];
        $userNome = $_SESSION['user_nome'];

        $model = new Despesa($userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tokenRecebido = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $tokenRecebido)) {
                $errors[] = 'Token de segurança inválido. Recarregue a página e tente novamente.';
            }

            $action = $_POST['action'] ?? 'criar';

            if (empty($errors) && $action === 'remover') {
                $despesaId = trim($_POST['despesa_id'] ?? '');

                if ($despesaId === '') {
                    $errors[] = 'identificador da despesa inválido';
                } elseif ($model->removerDespesa($despesaId)) {
                    $_SESSION['successMessage'] = 'despesa removida com sucesso';
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $errors[] = 'despesa não encontrada para remoção';
                }
            }

            if (empty($errors) && $action !== 'remover') {
                $data['nome'] = trim($_POST['nome'] ?? '');
                $data['valor'] = str_replace(',', '.', trim($_POST['valor'] ?? ''));
                $data['data'] = trim($_POST['data'] ?? '');

                if ($data['nome'] === '') {
                    $errors[] = 'informe o nome da despesa';
                }

                if (!is_numeric($data['valor']) || (float)$data['valor'] <= 0) {
                    $errors[] = 'valor inválido';
                }

                $date = \DateTime::createFromFormat('Y-m-d', $data['data']);
                $dateErrors = \DateTime::getLastErrors();

                if ($data['data'] === '' || !$date || ($dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
                    $errors[] = 'informe uma data válida';
                } else {
                    $data['data'] = $date->format('Y-m-d');
                }

                if (empty($errors)) {
                    $model->salvarDespesa($data);
                    $_SESSION['successMessage'] = 'despesa adicionada com sucesso';
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                }
            }
        }

        if (!empty($_SESSION['successMessage'])) {
            $successMessage = $_SESSION['successMessage'];
            unset($_SESSION['successMessage']);
        }

        $listaDespesas = $model->buscarDespesas();
        $historicoCompleto = $model->buscarHistoricoCompleto();

        // Calcula o total das despesas exibidas (preparado para filtros futuros)
        $totalDespesas = array_sum(array_column($listaDespesas, 'valor'));

        require_once __DIR__ . '/../Views/despesa_form.php';
    }

    public function editar() {
        Auth::verificar();

        $errors = [];
        $successMessage = '';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrfToken = $_SESSION['csrf_token'];

        $userId = $_SESSION['user_id'];

        $model = new Despesa($userId);

        $id = $_GET['id'] ?? '';
        $despesa = $model->buscarPorId($id);

        if (!$despesa) {
            header('Location: index.php?route=dashboard');
            exit;
        }

        $data = [
            'nome' => $despesa['nome'],
            'valor' => $despesa['valor'],
            'data' => $despesa['data'],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tokenRecebido = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $tokenRecebido)) {
                $errors[] = 'Token de segurança inválido. Recarregue a página e tente novamente.';
            }

            if (empty($errors)) {
                $data['nome'] = trim($_POST['nome'] ?? '');
                $data['valor'] = str_replace(',', '.', trim($_POST['valor'] ?? ''));
                $data['data'] = trim($_POST['data'] ?? '');

                if ($data['nome'] === '') {
                    $errors[] = 'informe o nome da despesa';
                }

                if (!is_numeric($data['valor']) || (float)$data['valor'] <= 0) {
                    $errors[] = 'valor inválido';
                }

                $date = \DateTime::createFromFormat('Y-m-d', $data['data']);
                $dateErrors = \DateTime::getLastErrors();

                if ($data['data'] === '' || !$date || ($dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
                    $errors[] = 'informe uma data válida';
                } else {
                    $data['data'] = $date->format('Y-m-d');
                }

                if (empty($errors)) {
                    if ($model->editarDespesa($id, $data)) {
                        $_SESSION['successMessage'] = 'despesa editada com sucesso';
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        header('Location: index.php?route=dashboard');
                        exit;
                    }

                    $errors[] = 'não foi possível salvar as alterações';
                }
            }
        }

        require_once __DIR__ . '/../Views/editar_despesa.php';
    }
}