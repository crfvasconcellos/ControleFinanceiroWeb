<div align="center">

<img src="BrasaControleFinanceiro1x1.png" alt="Logo Controle Financeiro" width="180" />

# 💰 Controle Financeiro Pessoal

**Aplicação web para registro, acompanhamento e análise de gastos pessoais.**

Desenvolvido como parte da disciplina de Engenharia de Software — **Universidade Federal do Tocantins (UFT)**

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![License](https://img.shields.io/badge/Licença-Acadêmica-blue)](#-licença)

</div>

---

# 📑 Sumário

- [👥 Integrantes](#-integrantes)
- [🏗️ Arquitetura do Sistema](#️-arquitetura-do-sistema-mvc)
- [💾 Banco de Dados](#-banco-de-dados)
- [🚀 Como Executar](#-como-executar-localmente)
- [🧪 Testes Automatizados](#-testes-automatizados)
- [🧪 API REST](#-api-rest)
- [📦 Funcionalidades](#-funcionalidades)
- [📅 Planejamento de Sprints](#-planejamento-de-sprints)
- [📊 Backlog do Produto](#-backlog-do-produto)
- [🌿 GitFlow](#-gestão-de-versões-gitflow)
- [🛠️ Tecnologias](#️-tecnologias-utilizadas)
- [📝 Licença](#-licença)

---

# 👥 Integrantes

| Nome |
|------|
| Brendo Henrique |
| Claudio Vasconcellos |
| Otavio Augusto |
| Samir Batista |
| Tiago Veras |

**Professor Orientador:** Edeilson Milhomem da Silva

---

# 🏗️ Arquitetura do Sistema (MVC)

A aplicação segue o padrão **MVC (Model-View-Controller)**.

```text
Projeto/
├── app/
│   ├── Config/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Models/
│   └── Views/
├── database/
│   └── schema.sql
├── public/
│   ├── assets/
│   ├── uploads/
│   └── index.php
└── src/
```

| Camada | Pasta | Responsabilidade |
|--------|-------|------------------|
| **Model** | `app/Models` | Persistência e regras de negócio |
| **View** | `app/Views` | Interface do usuário |
| **Controller** | `app/Controllers` | Processamento das requisições |

---

# 💾 Banco de Dados

O sistema utiliza **MySQL/MariaDB** com queries parametrizadas via **PDO**.

## Variáveis de Ambiente

| Variável | Valor Padrão |
|----------|---------------|
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `controle_financeiro` |
| `DB_USER` | `controle_app` |
| `DB_PASS` | `ControleApp@2026!` |

## Estrutura Principal

| Tabela | Função |
|--------|--------|
| `usuarios` | Contas de usuário |
| `despesas` | Registro de gastos |
| `saldos` | Registro de entradas |
| `despesas_recorrentes` | Gastos e saldos fixos |

---

# 🚀 Como Executar Localmente

## Pré-requisitos

- PHP 8.0+
- MySQL ou MariaDB
- Extensão `pdo_mysql`

## 1. Clone o Repositório

```bash
git clone [url-do-repositorio]
cd ControleFinanceiroWeb
```

## 2. Crie o Banco de Dados

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS controle_financeiro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 3. Importe o Schema

```bash
mysql -u root -p controle_financeiro < Projeto/database/schema.sql
```

## 4. Inicie o Servidor

```bash
cd Projeto/public
php -S localhost:8000
```

## 5. Acesse

```txt
http://localhost:8000
```

---

# 🧪 Testes Automatizados

A suíte de testes usa **PHPUnit 9** com integração real ao banco de dados de teste.


## 📋 Pré-requisitos

| Requisito | Versão |
|-----------|--------|
| PHP | 8.0+ |
| Extensão `pdo_mysql` | — |
| Extensão `xml` | — |
| Extensão `mbstring` | — |
| MySQL/MariaDB | — |
| Composer | — |


## 🗄️ Configurar o Banco de Dados de Teste

Os testes rodam em um banco separado (`controle_financeiro_test`) para nunca tocar nos dados reais.

Acesse o MySQL e execute:

```sql
CREATE DATABASE IF NOT EXISTS controle_financeiro_test;
GRANT ALL PRIVILEGES ON controle_financeiro_test.* TO 'controle_app'@'localhost';
FLUSH PRIVILEGES;
```

> As tabelas são criadas automaticamente pelo sistema de migrations ao rodar os testes pela primeira vez.


## ▶️ Executar os Testes

```bash
./vendor/bin/phpunit
```

O arquivo `phpunit.xml` na raiz do projeto já configura tudo automaticamente: bootstrap, diretório de testes, cores e verbosidade.


## 🔍 Filtros Úteis

```bash
# Rodar um arquivo específico
./vendor/bin/phpunit tests/Controllers/AuthControllerTest.php

# Rodar um método específico
./vendor/bin/phpunit --filter testLoginGetExibeFormulario

# Rodar só os testes de Controllers
./vendor/bin/phpunit tests/Controllers/

# Rodar só os testes de Models
./vendor/bin/phpunit tests/Models/
```

---

## 📊 Relatórios

**Saída detalhada no terminal:**
```bash
./vendor/bin/phpunit --testdox
```

**Cobertura de código no terminal** (requer Xdebug):
```bash
./vendor/bin/phpunit --coverage-text
```

> Para relatórios de cobertura, instale o Xdebug: `sudo apt-get install php8.1-xdebug`

---

## 🗂️ Estrutura dos Testes

```text
tests/
├── bootstrap.php               # Inicialização: autoload, banco de teste, sessão
├── create_test_db.php          # Script auxiliar para criar o banco manualmente
├── Controllers/
│   ├── AuthControllerTest.php  # Login, registro, logout, CSRF
│   ├── ApiControllerTest.php   # Autenticação por API key, transações, saldo
│   └── DespesaControllerTest.php # Filtros de despesas
└── Models/
    ├── DespesaTest.php         # CRUD de despesas
    ├── SaldoTest.php           # CRUD de saldos
    └── UsuarioTest.php         # Registro e autenticação de usuários
```

---

## ⚠️ Observações

- Os testes de `Controllers` e `Models` usam o banco real — certifique-se de que o MySQL está rodando antes de executar.
- Cada teste limpa seus próprios dados no `setUp` e `tearDown`, garantindo isolamento.O banco de produção (`controle_financeiro`) nunca é afetado pelos testes.

# 🧪 API REST

A aplicação possui uma API protegida por chave de autenticação.

## Como Utilizar

1. Crie uma conta admin
2. Gere a API Key no dashboard
3. Envie no header:

```http
X-API-KEY: sua-chave
```

## Endpoint

```http
GET /?route=api_despesas
```

📄 Documentação completa em:

```txt
API_DOCS.md
```

---

# 📦 Funcionalidades

## 💳 Transações

- Adicionar, editar e excluir despesas
- Adicionar saldos e entradas
- Upload de comprovantes PDF
- Histórico de transações
- Exclusão lógica com lixeira

## 📊 Dashboard

- Resumo financeiro
- Gastos mensais
- Filtros por categoria
- Busca por nome
- Destaque de maior despesa

## 🔄 Recorrentes

- Despesas fixas mensais
- Saldos fixos mensais
- Geração automática
- Pausar e reativar recorrências

## 🔐 Autenticação

- Login e registro
- Senhas criptografadas com bcrypt
- Sessões PHP
- Proteção CSRF

---

# 📅 Planejamento de Sprints

## Sprint 1 — Gerenciamento de Dados

> Criação da base utilizável do sistema.

- **US01** – Adicionar Despesa
- **US02** – Listar Despesas

## Sprint 2 — Registro e Visualização

> Controle e correção de informações.

- **US03** – Remover Despesa
- **US04** – Visualizar Total de Gastos
- **US05** – Editar Despesa
- **US06** – Validar Campos Obrigatórios
- **US07** – Implementar Banco de Dados SQL
- **US08** – Listar Histórico de Despesas

## Sprint 3 — Organização e Análise

> Experiência do usuário e suporte à tomada de decisão.

- **US09** – Categorizar Despesas
- **US10** – Atualizar Interface
- **US11** – Adicionar Saldo
- **US12** – Indexar Comprovante PDF
- **US13** – Filtrar Despesas
- **US14** – Buscar Despesas por Nome
- **US15** – Exibir Gastos por Categoria
- **US16** – Destacar Maior Despesa do Período
- **US17** – Despesas Recorrentes (Fixas)
- **US18** – Saldos Recorrentes (Fixos)

## Sprint 4 — Melhorias e Relatórios

> Recursos de usabilidade e análise financeira.

- **US19** – Remover Gastos Fixos do Histórico ao Excluir
- **US20** – Implementar Modo Escuro na Interface
- **US21** – Exibir Média de Gastos Mensais

## Sprint 5 — Exportação, Perfil e Inteligência Financeira

> Empoderar o usuário com dados exportáveis, visão de perfil e alertas financeiros inteligentes.

- **US22** – Exportar Despesas em CSV
- **US23** – Editar Perfil do Usuário (Nome e Senha)
- **US24** – Alertas Visuais de Orçamento
- **US25** – Comparativo Mês Atual vs. Anterior
- **US26** – Restaurar Transação da Lixeira

---

# 📊 Backlog do Produto

As estimativas seguem a **Sequência de Fibonacci**.

| ID | User Story | Pts | Status | Critérios de Aceitação |
|:---|:-----------|:---:|:------:|:-----------------------|
| US01 | Adicionar despesa para controlar gastos | 5 | ✅ | Validar nome, valor positivo e data |
| US02 | Visualizar despesas para acompanhar gastos | 5 | ✅ | Lista atualizada com nome, valor e data |
| US03 | Excluir despesa para corrigir erros | 3 | ✅ | Remoção imediata da lista |
| US04 | Ver total de gastos | 3 | ✅ | Somatório automático |
| US05 | Editar despesa | 5 | ✅ | Alteração de nome, valor e data |
| US06 | Validar campos obrigatórios | 3 | ✅ | Impedir dados inválidos |
| US07 | Persistência em banco SQL | 8 | ✅ | CRUD completo |
| US08 | Listar histórico de despesas | 5 | ✅ | Histórico funcional |
| US09 | Categorizar despesas | 5 | ✅ | Associação de categorias |
| US10 | Atualizar interface | 5 | ✅ | Interface responsiva |
| US11 | Adicionar saldo | 5 | ✅ | Cadastro de entradas |
| US12 | Indexar comprovante PDF | 3 | ✅ | Upload funcional |
| US13 | Filtrar despesas | 3 | ✅ | Filtro por categoria |
| US14 | Buscar despesas por nome | 2 | ✅ | Busca textual |
| US15 | Exibir gastos por categoria | 5 | ✅ | Totais agrupados |
| US16 | Destacar maior despesa | 3 | ✅ | Identificação automática |
| US17 | Despesas recorrentes mensais | 5 | ✅ | Geração automática |
| US18 | Saldos recorrentes mensais | 3 | ✅ | Entradas automáticas |
| US19 | Remover gastos fixos do histórico | 3 | ✅ | Remover registros vinculados |
| US20 | Implementar modo escuro | 5 | ✅ | Alternância de tema |
| US21 | Exibir média de gastos mensais | 3 | ✅ | Média automática |
| US22 | Exportar despesas em CSV para analisar em planilhas | 3 | ✅ | Download CSV com filtros aplicados |
| US23 | Editar perfil do usuário (nome e senha) | 5 | 📋 | Modal de perfil com validação de senha |
| US24 | Alertas visuais de orçamento ao se aproximar do limite | 3 | 📋 | Notificação no dashboard a 75%, 95% e acima |
| US25 | Comparativo de gastos entre mês atual e anterior | 5 | 📋 | Card com variação percentual e indicador visual |
| US26 | Restaurar transação excluída da lixeira | 3 | 📋 | Botão restaurar no histórico, reativar `deletado_em` |

> ✅ Concluído • 📋 Planejado

---

# 🌿 Gestão de Versões (GitFlow)

```text
main
 └── develop
      ├── feature/US01
      ├── feature/US11
      ├── feature/US17
      └── feature/US18
```

| Branch | Finalidade |
|--------|-------------|
| `main` | Produção |
| `develop` | Integração |
| `feature/*` | Desenvolvimento de funcionalidades |

---

## 📋 Product Backlog: Controle Financeiro Pessoal

| ID   | User Story | Story Points | Critérios de Aceitação |
|------|-----------|--------------|------------------------|
| US01 | Como usuário, quero adicionar uma despesa para controlar meus gastos | 5 | - Inserir nome da despesa<br>- Inserir valor (não pode ser negativo ou vazio)<br>- Inserir data<br>- Dados devem ser salvos corretamente |
| US02 | Como usuário, quero visualizar minhas despesas para acompanhar meus gastos | 5 | - Exibir lista de despesas<br>- Mostrar nome, valor e data<br>- Atualizar automaticamente após novas inserções |
| US03 | Como usuário, quero excluir uma despesa para corrigir erros | 3 | - Botão de exclusão disponível<br>- Remoção imediata da lista |
| US04 | Como usuário, quero ver o total de gastos para entender quanto já gastei | 3 | - Cálculo automático do total<br>- Atualização ao adicionar ou remover despesas |
| US05 | Como usuário, quero categorizar minhas despesas para organizar meus gastos | 5 | - Criar categorias (ex: alimentação, transporte)<br>- Associar categoria à despesa |
| US06 | Como usuário, quero filtrar despesas por categoria para analisar meus gastos | 3 | - Selecionar categoria<br>- Exibir apenas despesas relacionadas |

---

## 🛠️ Tecnologias Utilizadas

| Tecnologia | Uso |
|-----------|-----|
| PHP 8.x | Backend |
| HTML5 + CSS3 | Interface |
| MySQL/MariaDB | Banco de dados |
| SVG | Gráficos |
| PDO | Persistência |
| CSS Modals | Componentes visuais |

---

# 📝 Licença

Projeto desenvolvido exclusivamente para fins acadêmicos na **Universidade Federal do Tocantins (UFT)**.

<div align="center">

Feito com ❤️ pela equipe de Engenharia de Software — UFT 2026

</div>
