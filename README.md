# Projeto Controle Financeiro Pessoal

Este projeto consiste em uma aplicação web desenvolvida como parte da disciplina de Engenharia de Software na **Universidade Federal do Tocantins (UFT)**. O objetivo principal é auxiliar usuários no registro, acompanhamento e análise de seus gastos pessoais através de uma interface intuitiva e funcional.

## 👥 Integrantes
- **Brendo Henrique**
- **Claudio Vasconcellos**
- **Otavio Augusto**
- **Samir Batista**
- **Tiago Veras**

**Professor Orientador:** Edeilson Milhomem da Silva

---

## 🏗️ Arquitetura do Sistema (MVC)
A aplicação foi estruturada utilizando o padrão arquitetural **MVC** (Model-View-Controller) para garantir a separação de responsabilidades e facilitar a manutenção do código:

* **Model (`app/Models`):** Responsável pela lógica de negócio, validação de dados e persistência.
* **View (`app/Views`):** Camada de apresentação, contendo os ficheiros PHP/HTML que compõem a interface do utilizador.
* **Controller (`app/Controllers`):** Intermediário que processa as requisições do utilizador, interage com o Model e seleciona a View a ser apresentada.

## 💾 Armazenamento de Dados
Atualmente, o sistema utiliza persistência em **MySQL/MariaDB** para contas de usuário e despesas. A aplicação executa CRUD completo com queries parametrizadas (PDO) e isolamento por usuário autenticado.

As credenciais podem ser configuradas por variáveis de ambiente:

- `DB_HOST` (padrão: `127.0.0.1`)
- `DB_PORT` (padrão: `3306`)
- `DB_NAME` (padrão: `controle_financeiro`)
- `DB_USER` (padrão: `root`)
- `DB_PASS` (padrão: vazio)

O schema SQL está disponível em `Projeto/database/schema.sql`.

## 🚀 Como Executar Localmente
Para rodar o projeto no seu ambiente de desenvolvimento, siga os passos abaixo:

1.  **Pré-requisitos:** Certifique-se de ter **PHP 8.0+** e **MySQL/MariaDB** instalados.
2.  **Clone o repositório:** `git clone [url-do-repositorio]`
3.  **Crie o banco de dados e usuário (usando a senha `suasenha` para o root no MySQL):**
    Abra o terminal (cmd ou PowerShell) e execute:
    ```bash
    mysql -u root -psuasenha -e "CREATE DATABASE IF NOT EXISTS controle_financeiro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -u root -psuasenha -e "CREATE USER IF NOT EXISTS 'controle_app'@'localhost' IDENTIFIED BY 'ControleApp@2026!'; GRANT ALL PRIVILEGES ON controle_financeiro.* TO 'controle_app'@'localhost'; FLUSH PRIVILEGES;"
    ```
4.  **Importe o schema:**
    ```bash
    mysql -u root -p controle_financeiro < Projeto/database/schema.sql
    ```
5.  **(Opcional) Configure variáveis de ambiente** `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` conforme o seu ambiente.
6.  **Inicie o servidor local:** Navegue até a pasta `Projeto/public` e inicie o servidor embutido do PHP:
    ```bash
    cd Projeto/public
    php -S localhost:8000
    ```
7.  **Acesse:** Abra o navegador e acesse `http://localhost:8000`.

### 🧪 Testando as APIs
Para testar o funcionamento da API de despesas:
1. Registre ou acesse uma conta com o nome **`admin`** e o e-mail **`admin@mail.uft.edu.br`**.
2. Na interface do sistema (dashboard), a opção de "Acesso à API" ficará visível no canto superior.
3. Copie a chave gerada e utilize-a enviando no cabeçalho `X-API-KEY` de suas requisições para a rota `/?route=api_despesas`.
4. Opcionalmente, pode testar através do navegador acessando diretamente `http://localhost:8000/?route=api_despesas`.

---

## 📅 Planejamento de Sprints

### Sprint 1: Gerenciamento de Dados
Foco na criação da base utilizável do sistema.
- **US01 – Adicionar Despesa:** Inserção de nome, valor e data (com persistência em banco de dados).
- **US02 – Listar Despesas:** Exibição clara dos gastos registrados com atualização automática.
### Sprint 2: Registro e Visualização 
Foco no controle e correção de informações.
- **US03 – Remover Despesa:** Funcionalidade para exclusão de registros incorretos.
- **US04 – Visualizar Total de Gastos:** Cálculo automático do somatório de todas as despesas.
- **US05 – Editar Despesa:** Permite alterar nome, valor e data de uma despesa já cadastrada.
- **US06 – Validar Campos Obrigatórios:** Verificação de preenchimento e formato correto dos dados antes do salvamento.
- **US07 – Implementar Banco de Dados SQL:** Estruturar persistência das despesas em MySQL ou MariaDB.
- **US08 - Listar Histórico de Despesas:** Histórico e Lixeira de Despesas: Implementação de sistema de exclusão lógica e visualização de registros excluídos.

### Sprint 3: Organização e Análise
Foco na experiência do usuário e suporte à tomada de decisão.
- **US09 – Categorizar Despesas:** Criação e associação de categorias (ex: alimentação, transporte).
- **US10 – Atualizar Interface:** Renovar a interface gráfica com design moderno e amigável.
- **US11 – Adicionar Saldo:** Permitir a inserção de saldos positivos para compensar gastos.
- **US12 – Indexar Comprovante:** Funcionalidade para anexar comprovantes em formato PDF nas transações.
- **US13 – Filtrar Despesas:** Visualização segmentada por categorias.
- **US14 – Buscar Despesas por Nome:** Localização rápida de registros com base no título da despesa.
- **US15 – Exibir Gastos por Categoria:** Apresentação do total gasto em cada categoria cadastrada.
- **US16 – Destacar Maior Despesa do Período:** Identificação automática do maior gasto para apoio à análise financeira.

---
## 📅 Planejamento e Backlog do Produto
O desenvolvimento foi dividido em Sprints para entrega incremental de valor. As estimativas de esforço (**Story Points**) seguem a **Sequência de Fibonacci** (1, 2, 3, 5, 8...), onde valores maiores indicam maior complexidade ou incerteza.

| ID | User Story | Pontos | Status | Critérios de Aceitação |
| :--- | :--- | :--- | :--- | :--- |
| **US01** | Adicionar despesa para controlar gastos | 5 | **Concluído** | Validar nome, valor positivo e data; salvar no banco de dados. |
| **US02** | Visualizar despesas para acompanhar gastos | 5 | **Concluído** | Exibir lista atualizada com nome, valor e data. |
| **US03** | Excluir despesa para corrigir erros | 3 | **Concluído** | Botão de remoção e atualização imediata do armazenamento. |
| **US04** | Ver total de gastos para entender despesas | 3 | **Concluído** | Cálculo automático do somatório de todas as despesas. |
| **US05** | Editar despesa para corrigir informações | 5 | **Concluído** | Permitir alteração de nome, valor e data de despesas já cadastradas. |
| **US06** | Validar campos obrigatórios no cadastro | 3 | **Concluído** | Impedir salvamento com campos vazios ou formatos inválidos, exibindo mensagem de erro. |
| **US07** | Implementar persistência em banco SQL | 8 | **Concluído** | Migrar armazenamento para MySQL/MariaDB com operações de CRUD funcionando. |
| **US08** | Listar Histórico de Despesas | 5 | **Concluído** | Listar histórico de todas despesas ativas e excluídas. |
| **US09** | Categorizar despesas para organização | 5 | *Planejado* | Criar e associar categorias (ex: alimentação) aos registros. |
| **US10** | Atualizar interface do sistema | 5 | **Concluído** | Interface modernizada e mais amigável |
| **US11** | Adicionar entradas e saldo | 5 | **Concluído** | Botões e formulários para adicionar valores positivos, unificando a exibição no histórico. |
| **US12** | Indexar comprovante PDF | 3 | **Concluído** | Adição de campo para upload de arquivos `.pdf` como comprovantes das transações. |
| **US13** | Filtrar despesas por categoria | 3 | *Planejado* | Selecionar categoria e exibir apenas registros relacionados. |
| **US14** | Buscar despesas por nome | 2 | *Planejado* | Permitir busca textual por título da despesa e exibir resultados relevantes. |
| **US15** | Exibir gastos por categoria | 5 | *Planejado* | Apresentar o total acumulado para cada categoria cadastrada. |
| **US16** | Destacar maior despesa do período | 3 | *Planejado* | Identificar automaticamente e mostrar o maior gasto no intervalo selecionado. |

## 🌿 Gestão de Versões (GitFlow)
O projeto adota o fluxo de trabalho **GitFlow** para organizar o desenvolvimento:
* **`main`:** Armazena o código estável e pronto para produção.
* **`develop`:** Ramo de integração para novas funcionalidades.
* **`feature/`:** Ramos temporários criados a partir da `develop` para o desenvolvimento de User Stories específicas.

## 🛠️ Tecnologias Utilizadas
* **Linguagem:** PHP 8.x (Nativo)
* **Interface:** HTML5 e CSS3
* **Persistência:** MySQL/MariaDB (PDO)

---
## 📝 Licença
Este projeto foi desenvolvido exclusivamente para fins acadêmicos na Universidade Federal do Tocantins (UFT).
