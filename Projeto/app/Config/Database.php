<?php

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

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
                    "Falha de autenticação no MySQL/MariaDB para o usuário '{$user}'. " .
                    "O servidor está exigindo um plugin não suportado pelo cliente PHP (auth_gssapi_client). " .
                    "Crie um usuário de aplicação com mysql_native_password e configure DB_USER/DB_PASS. " .
                    "Exemplo SQL: CREATE USER 'controle_app'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('senha_forte'); " .
                    "GRANT ALL PRIVILEGES ON {$name}.* TO 'controle_app'@'localhost'; FLUSH PRIVILEGES. " .
                    "Depois exporte DB_USER=controle_app e DB_PASS=senha_forte antes de iniciar o servidor PHP."
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
                criado_em DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS despesas (
                id VARCHAR(64) PRIMARY KEY,
                usuario_id VARCHAR(64) NOT NULL,
                nome VARCHAR(140) NOT NULL,
                valor DECIMAL(10,2) NOT NULL,
                data DATE NOT NULL,
                criado_em DATETIME NOT NULL,
                CONSTRAINT fk_despesas_usuario
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                    ON DELETE CASCADE,
                INDEX idx_despesas_usuario_data (usuario_id, data)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );
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
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key === '' || getenv($key) !== false) {
                continue;
            }

            $value = trim($value, "\"'");
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
