<?php

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return '' === $needle || false !== strpos($haystack, $needle);
    }
}

class Database {
    private static ?PDO $connection = null;
    private static bool $envLoaded = false;

    public static function getConnection(): PDO {
        if (self::$connection !== null) {
            return self::$connection;
        }

        self::loadLocalEnv();

        if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
            $phpIni = php_ini_loaded_file() ?: 'php.ini não identificado';
            throw new RuntimeException(
                'Driver PDO MySQL não está habilitado. Ative a extensão pdo_mysql no arquivo ' .
                $phpIni .
                ' e reinicie o servidor PHP.'
            );
        }

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_NAME') ?: 'controle_financeiro';
        $user = getenv('DB_USER') ?: 'controle_app';
        $pass = getenv('DB_PASS') ?: 'ControleApp@2026!';

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

        try {
            self::$connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            self::migrate(self::$connection);

            return self::$connection;
        } catch (PDOException $e) {
            $mensagemOriginal = $e->getMessage();
            $codigo = (string) $e->getCode();

            if (str_contains($mensagemOriginal, 'auth_gssapi_client') || $codigo === '2054') {
                throw new RuntimeException(
                    "Falha de autenticação no MySQL. Use mysql_native_password para o usuário '{$user}'."
                );
            }

            throw new RuntimeException('Falha ao conectar no banco de dados: ' . $e->getMessage());
        }
    }

    private static function migrate(PDO $connection): void {
        $connection->exec(
            'CREATE TABLE IF NOT EXISTS usuarios (
                id VARCHAR(64) PRIMARY KEY,
                nome VARCHAR(120) NOT NULL,
                email VARCHAR(160) NOT NULL UNIQUE,
                senha VARCHAR(255) NOT NULL,
                api_key VARCHAR(64) UNIQUE,
                criado_em DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS despesas (
                id VARCHAR(64) PRIMARY KEY,
                usuario_id VARCHAR(64) NOT NULL,
                nome VARCHAR(140) NOT NULL,
                descricao VARCHAR(200) DEFAULT NULL,
                valor DECIMAL(10,2) NOT NULL,
                data DATE NOT NULL,
                data_termino DATE DEFAULT NULL,
                comprovante VARCHAR(255) DEFAULT NULL,
                icone VARCHAR(10) DEFAULT \'📄\',
                criado_em DATETIME NOT NULL,
                deletado_em DATETIME DEFAULT NULL,
                CONSTRAINT fk_despesas_usuario
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                    ON DELETE CASCADE,
                INDEX idx_despesas_usuario_data (usuario_id, data),
                INDEX idx_despesas_soft_delete (usuario_id, deletado_em)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS saldos (
                id VARCHAR(64) PRIMARY KEY,
                usuario_id VARCHAR(64) NOT NULL,
                nome VARCHAR(140) NOT NULL,
                descricao VARCHAR(200) DEFAULT NULL,
                valor DECIMAL(10,2) NOT NULL,
                data DATE NOT NULL,
                data_termino DATE DEFAULT NULL,
                comprovante VARCHAR(255) DEFAULT NULL,
                icone VARCHAR(10) DEFAULT \'💵\',
                criado_em DATETIME NOT NULL,
                deletado_em DATETIME DEFAULT NULL,
                CONSTRAINT fk_saldos_usuario
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                    ON DELETE CASCADE,
                INDEX idx_saldos_usuario (usuario_id),
                INDEX idx_saldos_soft_delete (usuario_id, deletado_em)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );

        // Tabela de despesas recorrentes (templates mensais)
        $connection->exec(
            'CREATE TABLE IF NOT EXISTS despesas_recorrentes (
                id VARCHAR(64) PRIMARY KEY,
                usuario_id VARCHAR(64) NOT NULL,
                nome VARCHAR(140) NOT NULL,
                descricao VARCHAR(200) DEFAULT NULL,
                valor DECIMAL(10,2) NOT NULL,
                dia_vencimento INT NOT NULL DEFAULT 1,
                icone VARCHAR(10) DEFAULT \'🔄\',
                data_inicio DATE DEFAULT NULL,
                ativo TINYINT(1) DEFAULT 1,
                criado_em DATETIME NOT NULL,
                CONSTRAINT fk_recorrentes_usuario
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                    ON DELETE CASCADE,
                INDEX idx_recorrentes_usuario (usuario_id),
                INDEX idx_recorrentes_ativo (usuario_id, ativo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );

        // Adiciona coluna recorrente_id na tabela despesas (se não existir)
        $cols = $connection->query('SHOW COLUMNS FROM despesas LIKE \'recorrente_id\'')->fetchAll();
        if (empty($cols)) {
            $connection->exec(
                'ALTER TABLE despesas ADD COLUMN recorrente_id VARCHAR(64) DEFAULT NULL AFTER icone'
            );
        }

        // Adiciona coluna data_inicio na tabela despesas_recorrentes (se não existir)
        $cols2 = $connection->query('SHOW COLUMNS FROM despesas_recorrentes LIKE \'data_inicio\'')->fetchAll();
        if (empty($cols2)) {
            $connection->exec(
                'ALTER TABLE despesas_recorrentes ADD COLUMN data_inicio DATE DEFAULT NULL AFTER icone'
            );
            // Preenche data_inicio com criado_em para registros existentes
            $connection->exec(
                'UPDATE despesas_recorrentes SET data_inicio = DATE(criado_em) WHERE data_inicio IS NULL'
            );
        }
    }

    private static function loadLocalEnv(): void {
        if (self::$envLoaded) {
            return;
        }

        self::$envLoaded = true;
        $envPath = __DIR__ . '/../../../.env';
        
        if (!is_file($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim(trim($value), "\"'");

            if ($key !== '' && getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}