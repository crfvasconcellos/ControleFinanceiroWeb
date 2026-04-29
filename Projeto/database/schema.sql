CREATE TABLE IF NOT EXISTS usuarios (
    id VARCHAR(64) PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    criado_em DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS despesas (
    id VARCHAR(64) PRIMARY KEY,
    usuario_id VARCHAR(64) NOT NULL,
    nome VARCHAR(140) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data DATE NOT NULL,
    criado_em DATETIME NOT NULL,
    deletado_em DATETIME DEFAULT NULL, 
    
    CONSTRAINT fk_despesas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    
    INDEX idx_despesas_usuario_data (usuario_id, data),
    INDEX idx_despesas_soft_delete (usuario_id, deletado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;