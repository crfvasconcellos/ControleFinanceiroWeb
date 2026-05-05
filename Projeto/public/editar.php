<?php

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Models/Despesa.php';
require_once __DIR__ . '/../app/Models/Saldo.php';
require_once __DIR__ . '/../app/Controllers/DespesaController.php';
require_once __DIR__ . '/../app/Middleware/Auth.php';

use App\Controllers\DespesaController;

$app = new DespesaController();
$app->editar();
