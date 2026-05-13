CREATE TABLE IF NOT EXISTS usuarios (
    id VARCHAR(64) PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    api_key VARCHAR(64) UNIQUE,
    criado_em DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS despesas (
    id VARCHAR(64) PRIMARY KEY,
    usuario_id VARCHAR(64) NOT NULL,
    nome VARCHAR(40) NOT NULL,
    descricao VARCHAR(150) DEFAULT NULL,
    valor DECIMAL(9,2) NOT NULL,
    data DATE NOT NULL,
    data_termino DATE DEFAULT NULL,
    comprovante VARCHAR(255) DEFAULT NULL,
    icone VARCHAR(10) DEFAULT '📄',
    recorrente_id VARCHAR(64) DEFAULT NULL,
    criado_em DATETIME NOT NULL,
    deletado_em DATETIME DEFAULT NULL,
    
    CONSTRAINT fk_despesas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    
    INDEX idx_despesas_usuario_data (usuario_id, data),
    INDEX idx_despesas_soft_delete (usuario_id, deletado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS saldos (
    id VARCHAR(64) PRIMARY KEY,
    usuario_id VARCHAR(64) NOT NULL,
    nome VARCHAR(40) NOT NULL,
    descricao VARCHAR(150) DEFAULT NULL,
    valor DECIMAL(9,2) NOT NULL,
    data DATE NOT NULL,
    data_termino DATE DEFAULT NULL,
    comprovante VARCHAR(255) DEFAULT NULL,
    icone VARCHAR(10) DEFAULT '💵',
    recorrente_id VARCHAR(64) DEFAULT NULL,
    criado_em DATETIME NOT NULL,
    deletado_em DATETIME DEFAULT NULL,

    CONSTRAINT fk_saldos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,

    INDEX idx_saldos_usuario (usuario_id),
    INDEX idx_saldos_soft_delete (usuario_id, deletado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS despesas_recorrentes (
    id VARCHAR(64) PRIMARY KEY,
    usuario_id VARCHAR(64) NOT NULL,
    nome VARCHAR(40) NOT NULL,
    descricao VARCHAR(150) DEFAULT NULL,
    valor DECIMAL(9,2) NOT NULL,
    dia_vencimento INT NOT NULL DEFAULT 1,
    icone VARCHAR(10) DEFAULT '🔄',
    tipo VARCHAR(10) DEFAULT 'saida',
    data_inicio DATE DEFAULT NULL,
    ativo TINYINT(1) DEFAULT 1,
    criado_em DATETIME NOT NULL,

    CONSTRAINT fk_recorrentes_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,

    INDEX idx_recorrentes_usuario (usuario_id),
    INDEX idx_recorrentes_ativo (usuario_id, ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;