<?php
// Carrega as dependências necessárias para os testes

require_once __DIR__ . '/../Projeto/app/Config/Database.php';
require_once __DIR__ . '/../Projeto/app/Models/Usuario.php';
require_once __DIR__ . '/../Projeto/app/Models/Despesa.php';
require_once __DIR__ . '/../Projeto/app/Models/Saldo.php';
require_once __DIR__ . '/../Projeto/app/Models/DespesaRecorrente.php';
require_once __DIR__ . '/../Projeto/app/Middleware/Auth.php';
require_once __DIR__ . '/../Projeto/app/Controllers/AuthController.php';
require_once __DIR__ . '/../Projeto/app/Controllers/DespesaController.php';
require_once __DIR__ . '/../Projeto/app/Controllers/ApiController.php';
