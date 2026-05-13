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

## 📑 Sumário

| # | Seção |
|:-:|:------|
| 1 | [👥 Integrantes](#-integrantes) |
| 2 | [🏗️ Arquitetura do Sistema](#%EF%B8%8F-arquitetura-do-sistema-mvc) |
| 3 | [💾 Banco de Dados](#-banco-de-dados) |
| 4 | [🚀 Como Executar](#-como-executar-localmente) |
| 5 | [🧪 Testando as APIs](#-testando-as-apis) |
| 6 | [📦 Funcionalidades](#-funcionalidades) |
| 7 | [📅 Sprints e Backlog](#-planejamento-de-sprints) |
| 8 | [📊 Backlog do Produto](#-backlog-do-produto) |
| 9 | [🌿 GitFlow](#-gestão-de-versões-gitflow) |
| 10 | [🛠️ Tecnologias](#%EF%B8%8F-tecnologias-utilizadas) |
| 11 | [📝 Licença](#-licença) |

---

## 👥 Integrantes

| Nome |
|------|
| Brendo Henrique |
| Claudio Vasconcellos |
| Otavio Augusto |
| Samir Batista |
| Tiago Veras |

**Professor Orientador:** Edeilson Milhomem da Silva

<p align="right"><a href="#-sumário">⬆ voltar ao topo</a></p>

---

## 🏗️ Arquitetura do Sistema (MVC)

A aplicação segue o padrão **MVC** (Model-View-Controller):

```
Projeto/
├── app/
│   ├── Config/          # Conexão com banco e migrations
│   ├── Controllers/     # Lógica de requisições (DespesaController, AuthController, ApiController)
│   ├── Middleware/       # Autenticação de rotas
│   ├── Models/           # Regras de negócio (Despesa, Saldo, DespesaRecorrente, Usuario)
│   └── Views/            # Interface HTML/PHP
├── database/
│   └── schema.sql       # Schema completo do banco
├── public/
│   ├── assets/          # CSS e imagens
│   ├── uploads/         # Comprovantes PDF
│   └── index.php        # Ponto de entrada (front controller)
└── src/                 # Autoloader
```

| Camada | Pasta | Responsabilidade |
|--------|-------|------------------|
| **Model** | `app/Models` | Persistência, validação e lógica de negócio |
| **View** | `app/Views` | Interface do usuário (PHP/HTML/CSS) |
| **Controller** | `app/Controllers` | Processamento de requisições e orquestração |

<p align="right"><a href="#-sumário">⬆ voltar ao topo</a></p>

---

## 💾 Banco de Dados

O sistema utiliza **MySQL/MariaDB** com queries parametrizadas (PDO) e isolamento por usuário.

**Variáveis de ambiente:**

| Variável | Padrão |
|----------|--------|
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `controle_financeiro` |
| `DB_USER` | `controle_app` |
| `DB_PASS` | `ControleApp@2026!` |

> **Schema:** [`Projeto/database/schema.sql`](Projeto/database/schema.sql)

**Tabelas:**

| Tabela | Função |
|--------|--------|
| `usuarios` | Contas de usuário com autenticação bcrypt |
| `despesas` | Registros de saídas (gastos) |
| `saldos` | Registros de entradas (receitas) |
| `despesas_recorrentes` | Templates de despesas/saldos fixos mensais |

<p align="right"><a href="#-sumário">⬆ voltar ao topo</a></p>

---

## 🚀 Como Executar Localmente

### Pré-requisitos
- **PHP 8.0+** com extensão `pdo_mysql`
- **MySQL** ou **MariaDB**

### Passo a passo

**1. Clone o repositório**
```bash
git clone [url-do-repositorio]
cd ControleFinanceiroWeb
```

**2. Crie o banco de dados e usuário**
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS controle_financeiro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER IF NOT EXISTS 'controle_app'@'localhost' IDENTIFIED BY 'ControleApp@2026!'; GRANT ALL PRIVILEGES ON controle_financeiro.* TO 'controle_app'@'localhost'; FLUSH PRIVILEGES;"
```

**3. Importe o schema**
```bash
mysql -u root -p controle_financeiro < Projeto/database/schema.sql
```

**4. (Opcional) Configure variáveis de ambiente**

Crie um arquivo `.env` na raiz do projeto ou defina as variáveis no sistema.

**5. Inicie o servidor**
```bash
cd Projeto/public
php -S localhost:8000
```

**6. Acesse:** [`http://localhost:8000`](http://localhost:8000)

<p align="right"><a href="#-sumário">⬆ voltar ao topo</a></p>

---

## 🧪 Testando as APIs

1. Registre uma conta com o nome **`admin`** e e-mail **`admin@mail.uft.edu.br`**
2. No dashboard, a opção **"Acesso à API"** ficará visível
3. Copie a chave gerada e envie no header `X-API-KEY`
4. Endpoint: `GET /?route=api_despesas`

> 📄 Documentação completa da API: [`API_DOCS.md`](API_DOCS.md)

<p align="right"><a href="#-sumário">⬆ voltar ao topo</a></p>

---

## 📦 Funcionalidades

### 💳 Transações
- Adicionar, editar e excluir **despesas** e **saldos**
- Campos: nome, descrição, valor, data, data de término (recorrência), ícone e comprovante PDF
- Exclusão lógica (lixeira) com histórico completo

### 📊 Dashboard
- Resumo financeiro: gastos no período, saldo disponível, total de transações
- Gráfico SVG dinâmico: gastos mensais (modo "Todos") ou gastos diários (modo mês)
- Filtros: mês, faixa de valor, tipo (entrada/saída) e categoria

### 🔄 Recorrentes Fixos
- Cadastro de **despesas fixas** e **saldos fixos** mensais
- Seletor visual de tipo: 📉 Despesa Fixa ou 📈 Saldo Fixo
- Geração automática de registros a cada mês
- Controle de ativação: pausar, reativar ou remover permanentemente
- Suporte a data de início para geração retroativa

### 🔐 Autenticação
- Registro e login com senha criptografada (bcrypt)
- Sessões PHP com proteção CSRF
- Isolamento total de dados por usuário

### 🌐 API REST
- Endpoint protegido por API Key (`X-API-KEY`)
- Acesso restrito a contas admin

<p align="right"><a href="#-sumário">⬆ voltar ao topo</a></p>

---

## 📅 Planejamento de Sprints

### Sprint 1 — Gerenciamento de Dados
> Criação da base utilizável do sistema.

- **US01** – Adicionar Despesa
- **US02** – Listar Despesas

### Sprint 2 — Registro e Visualização
> Controle e correção de informações.

- **US03** – Remover Despesa
- **US04** – Visualizar Total de Gastos
- **US05** – Editar Despesa
- **US06** – Validar Campos Obrigatórios
- **US07** – Implementar Banco de Dados SQL
- **US08** – Listar Histórico de Despesas

### Sprint 3 — Organização e Análise
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

<p align="right"><a href="#-sumário">⬆ voltar ao topo</a></p>

---

## 📊 Backlog do Produto

As estimativas seguem a **Sequência de Fibonacci** (1, 2, 3, 5, 8...).

| ID | User Story | Pts | Status | Critérios de Aceitação |
|:---|:-----------|:---:|:------:|:-----------------------|
| US01 | Adicionar despesa para controlar gastos | 5 | ✅ | Validar nome, valor positivo e data; salvar no BD |
| US02 | Visualizar despesas para acompanhar gastos | 5 | ✅ | Lista atualizada com nome, valor e data |
| US03 | Excluir despesa para corrigir erros | 3 | ✅ | Botão de remoção e atualização imediata |
| US04 | Ver total de gastos para entender despesas | 3 | ✅ | Cálculo automático do somatório |
| US05 | Editar despesa para corrigir informações | 5 | ✅ | Alteração de nome, valor e data |
| US06 | Validar campos obrigatórios no cadastro | 3 | ✅ | Impedir salvamento com dados inválidos |
| US07 | Implementar persistência em banco SQL | 8 | ✅ | CRUD completo em MySQL/MariaDB |
| US08 | Listar histórico de despesas | 5 | ✅ | Histórico com itens ativos e excluídos |
| US09 | Categorizar despesas para organização | 5 | ✅ | Criar e associar categorias aos registros |
| US10 | Atualizar interface do sistema | 5 | ✅ | Interface modernizada e responsiva |
| US11 | Adicionar entradas e saldo | 5 | ✅ | Formulários para valores positivos |
| US12 | Indexar comprovante PDF | 3 | ✅ | Upload de `.pdf` como comprovante |
| US13 | Filtrar despesas por categoria | 3 | ✅ | Selecionar categoria e filtrar registros |
| US14 | Buscar despesas por nome | 2 | ✅ | Busca textual por título |
| US15 | Exibir gastos por categoria | 5 | ✅ | Total acumulado por categoria |
| US16 | Destacar maior despesa do período | 3 | ✅ | Identificação automática do maior gasto |
| US17 | Despesas recorrentes (fixas) mensais | 5 | ✅ | Cadastro com dia de vencimento; geração automática; pausar/reativar |
| US18 | Saldos recorrentes (fixos) mensais | 3 | ✅ | Cadastro de saldos fixos com geração automática mensal |

> **Legenda:** ✅ Concluído · 📋 Planejado

<p align="right"><a href="#-sumário">⬆ voltar ao topo</a></p>

---

## 👤 User Stories

| ID | User Story |
|:---|:-----------|
| **US01** | Eu como usuário gostaria de adicionar uma despesa para controlar melhor meus gastos. |
| **US02** | Eu como usuário gostaria de visualizar minhas despesas no dashboard para acompanhar meu saldo diário. |
| **US03** | Eu como usuário gostaria de poder excluir uma despesa lançada erroneamente para corrigir os totais. |
| **US04** | Eu como usuário gostaria de ver o valor total das minhas despesas do período. |
| **US05** | Eu como usuário gostaria de editar uma despesa em vez de excluí-la, para preencher pequenos erros. |
| **US06** | Eu como usuário gostaria de ser validado nos campos obrigatórios de cadastro para evitar dados não inseridos. |
| **US07** | Eu como usuário gostaria de persistir meus dados com maior segurança num banco de dados. |
| **US08** | Eu como usuário gostaria de um histórico completo (lixeira) para saber quais gastos já excluí. |
| **US09** | Eu como usuário gostaria de categorizar minhas despesas usando ícones para entender pra onde o dinheiro vai. |
| **US10** | Eu como usuário gostaria de uma interface visualmente limpa e moderna para ser prático usar. |
| **US11** | Eu como usuário gostaria de informar não só gastos, mas também minhas entradas para cálculo automático. |
| **US12** | Eu como usuário gostaria de adicionar um comprovante PDF junto ao registo para referência futura. |
| **US13** | Eu como usuário gostaria de poder filtrar o que aparece no dashboard pelo mês ou categoria. |
| **US14** | Eu como usuário gostaria de usar uma barra de busca para encontrar transações pelo título delas rapidamente. |
| **US15** | Eu como usuário gostaria de ver um gráfico com o total acumulado que exiba a minha relação de gastos. |
| **US16** | Eu como usuário gostaria que as maiores despesas recebessem etiquetas de destaque automático. |
| **US17** | Eu como usuário gostaria de poder registrar uma conta fixa para que o sistema a recrie todo mês. |
| **US18** | Eu como usuário gostaria que saldos fixos como o salário também contassem sozinhos mensalmente. |

<p align="right"><a href="#-sumário">⬆ voltar ao topo</a></p>

---

## 🌿 Gestão de Versões (GitFlow)

```
main ─────────────────────────── produção estável
  └── develop ────────────────── integração
        ├── feature/US01 ─────── adicionar despesa
        ├── feature/US11 ─────── saldo
        ├── feature/US17 ─────── despesas recorrentes
        └── feature/US18 ─────── saldos recorrentes
```

| Branch | Finalidade |
|--------|-----------|
| `main` | Código estável, pronto para produção |
| `develop` | Integração de novas funcionalidades |
| `feature/*` | Desenvolvimento de User Stories específicas |

<p align="right"><a href="#-sumário">⬆ voltar ao topo</a></p>

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
| **PHP 8.x** | Backend nativo (sem frameworks) |
| **HTML5 + CSS3** | Interface (design system próprio com Inter font) |
| **MySQL/MariaDB** | Persistência (PDO com prepared statements) |
| **SVG** | Gráficos dinâmicos no dashboard |
| **CSS Modals** | Modais e interações sem JavaScript |

<p align="right"><a href="#-sumário">⬆ voltar ao topo</a></p>

---

## 📝 Licença

Este projeto foi desenvolvido exclusivamente para fins acadêmicos na **Universidade Federal do Tocantins (UFT)**.

<div align="center">

Feito com ❤️ pela equipe de Engenharia de Software — UFT 2026

</div>
