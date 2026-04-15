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

        $model = new Despesa();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data['nome'] = trim($_POST['nome'] ?? '');
            $data['valor'] = str_replace(',', '.', trim($_POST['valor'] ?? ''));
            $data['data'] = trim($_POST['data'] ?? '');

            if ($data['nome'] === '') {
                $errors[] = 'informe o nome da despesa';
            }

            if (!is_numeric($data['valor']) || (float)$data['valor'] <= 0) {
                $errors[] = 'valor inválido';
            }

            if (empty($errors)) {
                $model->salvarDespesa($data);
                $_SESSION['successMessage'] = 'despesa adicionada com sucesso';

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