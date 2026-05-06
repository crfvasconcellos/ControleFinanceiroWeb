<?php

namespace App\Controllers;

use App\Models\Despesa;
use App\Models\Saldo;
use App\Config\Database;

class ApiController {

    public function despesas() {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_POST['api_key'] ?? '';

        // curl ou fetch AJAX → retorna JSON
        if (!empty($_POST['api_key']) || $this->isCurl()) {
            [$code, $payload] = $this->buscarDados($apiKey);
            http_response_code($code);
            header('Content-Type: application/json');
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Browser → renderiza página de teste
        require_once __DIR__ . '/../Views/api_despesas.php';
    }

    private function buscarDados(string $apiKey): array {
        if (empty($apiKey)) {
            return [401, ['erro' => 'API key não informada']];
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, nome, email FROM usuarios WHERE api_key = :api_key LIMIT 1');
        $stmt->execute(['api_key' => $apiKey]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            return [403, ['erro' => 'API key inválida']];
        }

        $despesaModel = new Despesa($usuario['id']);
        $saldoModel   = new Saldo($usuario['id']);

        $despesas = array_map(fn($d) => array_merge($d, ['tipo' => 'saida']), $despesaModel->buscarDespesas());
        $entradas = array_map(fn($s) => array_merge($s, ['tipo' => 'entrada']), $saldoModel->buscarHistorico());

        $todas = array_merge($despesas, $entradas);
        usort($todas, fn($a, $b) => strcmp($b['data'], $a['data']));

        return [200, [
            'usuario'    => $usuario['nome'],
            'email'      => $usuario['email'],
            'saldo'      => [
                'disponivel'     => $saldoModel->saldoDisponivel(),
                'total_entradas' => $saldoModel->totalSaldo(),
            ],
            'transacoes' => $todas,
        ]];
    }

    private function isCurl(): bool {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return !empty($_SERVER['HTTP_X_API_KEY']) && !str_contains($accept, 'text/html');
    }
}