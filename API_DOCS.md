# Documentação da API - Controle Financeiro

## Autenticação

Todas as requisições exigem o header `X-API-KEY` com a chave de API do usuário.

**Header:**
```
X-API-KEY: {sua_chave_api}
```

**Status de erro:**
- `401` - API key não informada
- `403` - API key inválida

---

## Endpoints

### 1. Listar Despesas
**GET** `/api.php?route=despesas`

Lista todas as despesas do usuário autenticado.

**Exemplo:**
```bash
curl -H "X-API-KEY: sua_chave_api" \
  "http://localhost:8000/api.php?route=despesas"
```

**Resposta (200):**
```json
{
  "data": [
    {
      "id": "desp_123",
      "nome": "Aluguel",
      "descricao": "nome do condominio",
      "valor": "2000",
      "data": "2026-05-12",
      "icone": "🏠",
      "recorrente_mensal": 1,
      "recorrencia_meses": 5,
      "criado_em": "2026-05-12 10:30:45"
    }
  ]
}
```

---

### 2. Criar Despesa
**POST** `/api.php?route=despesas.criar`

Cria uma nova despesa com suporte a recorrência mensal.

**Body (JSON):**
```json
{
  "nome": "Aluguel",
  "descricao": "nome do condominio",
  "valor": 2000,
  "data": "2026-05-12",
  "icone": "🏠",
  "recorrente_mensal": true,
  "recorrencia_meses": 5
}
```

**Campos:**
- `nome` (obrigatório): Título da despesa (máx 30 chars)
- `descricao` (opcional): Descrição (máx 150 chars)
- `valor` (obrigatório): Valor positivo
- `data` (obrigatório): Data no formato YYYY-MM-DD
- `icone` (opcional): Emoji para a despesa (padrão: 📄)
- `recorrente_mensal` (opcional): boolean para ativar recorrência (padrão: false)
- `recorrencia_meses` (opcional): Quantidade de meses (1-24, padrão: 1)

**Exemplo:**
```bash
curl -X POST -H "X-API-KEY: sua_chave_api" \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Mercado",
    "valor": 150.50,
    "data": "2026-05-12",
    "icone": "🛒",
    "recorrente_mensal": true,
    "recorrencia_meses": 3
  }' \
  "http://localhost:8000/api.php?route=despesas.criar"
```

**Resposta (201):**
```json
{
  "mensagem": "3 despesa(s) criada(s)",
  "data": {
    "quantidade": 3
  }
}
```

---

### 3. Obter Saldo
**GET** `/api.php?route=saldo`

Retorna o saldo disponível e total de entradas.

**Exemplo:**
```bash
curl -H "X-API-KEY: sua_chave_api" \
  "http://localhost:8000/api.php?route=saldo"
```

**Resposta (200):**
```json
{
  "data": {
    "saldo_disponivel": 5250.50,
    "total_entradas": 7500.00
  }
}
```

---

### 4. Criar Saldo (Entrada/Renda)
**POST** `/api.php?route=saldo.criar`

Cria um novo saldo com suporte a recorrência mensal.

**Body (JSON):**
```json
{
  "nome": "Salário",
  "descricao": "Salário mensal",
  "valor": 2500,
  "data": "2026-05-12",
  "icone": "💵",
  "recorrente_mensal": true,
  "recorrencia_meses": 12
}
```

**Campos:**
- Mesmos campos que despesa.criar
- `icone` padrão: 💵

**Exemplo:**
```bash
curl -X POST -H "X-API-KEY: sua_chave_api" \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Salário",
    "valor": 2500,
    "data": "2026-05-12",
    "recorrente_mensal": true,
    "recorrencia_meses": 12
  }' \
  "http://localhost:8000/api.php?route=saldo.criar"
```

**Resposta (201):**
```json
{
  "mensagem": "12 saldo(s) criado(s)",
  "data": {
    "quantidade": 12
  }
}
```

---

### 5. Listar Todas as Transações
**GET** `/api.php?route=transacoes`

Lista todas as transações (despesas e entradas) do usuário.

**Exemplo:**
```bash
curl -H "X-API-KEY: sua_chave_api" \
  "http://localhost:8000/api.php?route=transacoes"
```

**Resposta (200):**
```json
{
  "data": [
    {
      "id": "saldo_456",
      "nome": "Salário",
      "valor": "2500",
      "data": "2026-05-12",
      "tipo": "entrada",
      "icone": "💵",
      "recorrente_mensal": 1,
      "recorrencia_meses": 12
    },
    {
      "id": "desp_123",
      "nome": "Aluguel",
      "valor": "2000",
      "data": "2026-05-12",
      "tipo": "saida",
      "icone": "🏠",
      "recorrente_mensal": 1,
      "recorrencia_meses": 5
    }
  ]
}
```

---

### 6. Editar Transação
**PUT** `/api.php?route=transacao.editar`

Edita uma transação existente (despesa ou saldo).

**Body (JSON):**
```json
{
  "id": "desp_123",
  "nome": "Aluguel Alto",
  "valor": 2100,
  "icone": "🏠",
  "recorrente_mensal": true,
  "recorrencia_meses": 3
}
```

**Campos:**
- `id` (obrigatório): ID da transação
- Outros campos são opcionais - apenas os informados serão atualizados

**Exemplo:**
```bash
curl -X PUT -H "X-API-KEY: sua_chave_api" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "desp_123",
    "recorrencia_meses": 6,
    "valor": 2200
  }' \
  "http://localhost:8000/api.php?route=transacao.editar"
```

**Resposta (200):**
```json
{
  "mensagem": "Transação atualizada com sucesso"
}
```

**Erro (404):**
```json
{
  "erro": "Transação não encontrada ou erro na atualização"
}
```

---

### 7. Remover Transação
**DELETE** `/api.php?route=transacao.deletar`

Remove uma transação existente.

**Body (JSON):**
```json
{
  "id": "desp_123"
}
```

**Exemplo:**
```bash
curl -X DELETE -H "X-API-KEY: sua_chave_api" \
  -H "Content-Type: application/json" \
  -d '{"id": "desp_123"}' \
  "http://localhost:8000/api.php?route=transacao.deletar"
```

**Resposta (200):**
```json
{
  "mensagem": "Transação removida com sucesso"
}
```

---

## Códigos de Status HTTP

| Código | Significado |
|--------|-------------|
| 200 | OK - Requisição bem-sucedida |
| 201 | Created - Recurso criado |
| 400 | Bad Request - Dados inválidos |
| 401 | Unauthorized - API key não informada |
| 403 | Forbidden - API key inválida |
| 404 | Not Found - Recurso não encontrado |
| 405 | Method Not Allowed - Método HTTP não permitido |

---

## Tratamento de Erros

**Validação falhou (400):**
```json
{
  "erro": "Validação falhou",
  "detalhes": [
    "Nome é obrigatório",
    "Valor inválido"
  ]
}
```

**Rota não encontrada (404):**
```json
{
  "erro": "Rota não encontrada",
  "hint": "Use: despesas, saldo, transacoes, despesas.criar, saldo.criar, transacao.editar, transacao.deletar"
}
```

---

## Recursos de Recorrência

A API suporta criação de transações recorrentes mensais:

- **`recorrente_mensal`**: `true` ou `false` (padrão: false)
- **`recorrencia_meses`**: Inteiro de 1 a 24 (padrão: 1)

Quando criada uma transação com `recorrente_mensal: true`, a API gera automaticamente uma transação para cada mês especificado em `recorrencia_meses`.

**Exemplo:** Criar um aluguel recorrente por 6 meses a partir de 2026-05-12 criará transações para:
- 2026-05-12
- 2026-06-12
- 2026-07-12
- 2026-08-12
- 2026-09-12
- 2026-10-12

**Nota sobre datas:** Se o dia do mês inicial não existir no mês recorrente (ex: 31/janeiro → fevereiro), a data será ajustada para o último dia do mês.

---

## Obtendo a API Key

A API key é gerada automaticamente para cada usuário. Você pode obtê-la:

1. Fazendo login no dashboard web
2. Contactando o administrador
3. Consultando o banco de dados na tabela `usuarios` coluna `api_key`

---

## Exemplos Completos

### Criar um salário recorrente (12 meses)
```bash
curl -X POST -H "X-API-KEY: sua_chave_api" \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Salário",
    "descricao": "Salário mensal empresa XYZ",
    "valor": 3500,
    "data": "2026-05-12",
    "icone": "💼",
    "recorrente_mensal": true,
    "recorrencia_meses": 12
  }' \
  "http://localhost:8000/api.php?route=saldo.criar"
```

### Editar recorrência de um aluguel
```bash
curl -X PUT -H "X-API-KEY: sua_chave_api" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "desp_123",
    "recorrencia_meses": 6
  }' \
  "http://localhost:8000/api.php?route=transacao.editar"
```

### Listar todas as transações e filtrar por tipo
```bash
curl -H "X-API-KEY: sua_chave_api" \
  "http://localhost:8000/api.php?route=transacoes" | jq '.data[] | select(.tipo=="saida")'
```
