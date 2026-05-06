<?php
// public/api.php

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Models/Usuario.php';
require_once __DIR__ . '/../app/Models/Despesa.php';
require_once __DIR__ . '/../app/Models/Saldo.php';

header('Content-Type: application/json');

// Autentica pelo header X-API-KEY
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

if (empty($apiKey)) {
    http_response_code(401);
    echo json_encode(['erro' => 'API key não informada']);
    exit;
}

// Busca o usuário pela api_key
$db = \App\Config\Database::getConnection();
$stmt = $db->prepare('SELECT id, nome, email FROM usuarios WHERE api_key = :api_key LIMIT 1');
$stmt->execute(['api_key' => $apiKey]);
$usuario = $stmt->fetch();

if (!$usuario) {
    http_response_code(403);
    echo json_encode(['erro' => 'API key inválida']);
    exit;
}

// Roteamento simples
$route = $_GET['route'] ?? '';

switch ($route) {

    case 'despesas':
        $model = new \App\Models\Despesa($usuario['id']);
        $despesas = $model->buscarDespesas();
        echo json_encode(['data' => $despesas]);
        break;

    case 'saldo':
        $model = new \App\Models\Saldo($usuario['id']);
        echo json_encode([
            'data' => [
                'saldo_disponivel' => $model->saldoDisponivel(),
                'total_entradas'   => $model->totalSaldo(),
            ]
        ]);
        break;

    case 'transacoes':
        $despesaModel = new \App\Models\Despesa($usuario['id']);
        $saldoModel   = new \App\Models\Saldo($usuario['id']);

        $despesas = array_map(fn($d) => array_merge($d, ['tipo' => 'saida']), $despesaModel->buscarDespesas());
        $entradas = array_map(fn($s) => array_merge($s, ['tipo' => 'entrada']), $saldoModel->buscarHistorico());

        $todas = array_merge($despesas, $entradas);
        usort($todas, fn($a, $b) => strcmp($b['data'], $a['data']));

        echo json_encode(['data' => $todas]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['erro' => 'Rota não encontrada']);
        break;
}