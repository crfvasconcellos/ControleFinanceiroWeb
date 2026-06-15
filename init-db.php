#!/usr/bin/env php
<?php
/**
 * Script de inicialização do banco de dados
 * Executa as migrations automáticas
 */

// Carrega autoload
require_once __DIR__ . '/Projeto/app/Config/Database.php';

use App\Config\Database;

echo "🔧 Inicializando banco de dados...\n";

try {
    // Tenta conectar e executar migrations
    $connection = Database::getConnection();
    echo "✅ Banco de dados conectado com sucesso!\n";
    echo "✅ Tabelas criadas/verificadas!\n";
} catch (Exception $e) {
    echo "❌ Erro ao conectar: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✨ Banco de dados pronto para uso!\n";
?>
