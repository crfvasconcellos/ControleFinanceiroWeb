<?php

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USER') ?: 'controle_app';
$pass = getenv('DB_PASS') ?: 'ControleApp@2026!';

$name = 'controle_financeiro_test';

try {
    $pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Cria o banco de dados de teste se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    echo "Banco de testes '{$name}' criado ou já existente com sucesso!\n";
} catch (PDOException $e) {
    echo "Erro ao criar o banco de testes: " . $e->getMessage() . "\n";
    exit(1);
}
