<?php

require_once __DIR__ . '/../app/Models/Despesa.php';
require_once __DIR__ . '/../app/Controllers/DespesaController.php';

use App\Controllers\DespesaController;

$app = new DespesaController();
$app->editar();
