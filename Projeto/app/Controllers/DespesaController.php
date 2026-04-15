<?php

namespace App\Controllers;
use App\Models\Despesa;

class DespesaController {
    public function create() {
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

        $model = new Despesa();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            $tokenRecebido = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $tokenRecebido)) {
                $errors[] = 'Token de segurança inválido. Recarregue a página e tente novamente.';
            }
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

                // Regenerar token CSRF após uso
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }
        }

        if (!empty($_SESSION['successMessage'])) {
            $successMessage = $_SESSION['successMessage'];
            unset($_SESSION['successMessage']);
        }

        $listaDespesas = $model->buscarDespesas();

        require_once __DIR__ . '/../Views/despesa_form.php';
    }
}