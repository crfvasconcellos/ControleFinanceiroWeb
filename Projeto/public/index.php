<?php

// Autoload dos arquivos
require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Models/Despesa.php';
require_once __DIR__ . '/../app/Models/Usuario.php';
require_once __DIR__ . '/../app/Models/Saldo.php';
require_once __DIR__ . '/../app/Models/DespesaRecorrente.php';
require_once __DIR__ . '/../app/Controllers/DespesaController.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Middleware/Auth.php';require_once __DIR__ . '/../app/Controllers/ApiController.php';


use App\Controllers\DespesaController;
use App\Controllers\AuthController;

// Roteamento simples por query string
$route = $_GET['route'] ?? 'dashboard';

switch ($route) {
    case 'login':
        $auth = new AuthController();
        $auth->login();
        break;

    case 'registro':
        $auth = new AuthController();
        $auth->registro();
        break;

    case 'logout':
        $auth = new AuthController();
        $auth->logout();
        break;

    case 'exportar_csv':
        $app = new DespesaController();
        $app->exportarCsv();
        break;

    case 'api_despesas':
    (new \App\Controllers\ApiController())->despesas();
    break;

    case 'dashboard':
    default:
        $app = new DespesaController();
        $app->create();
        break;
}