# US25 — Comparativo Mês Atual vs Anterior

## 📊 Visão Geral

A funcionalidade de **Comparativo Mês Atual vs Anterior** foi implementada para ajudar o usuário a acompanhar a evolução dos seus gastos mensais. Ela compara automaticamente o total de despesas do mês atual com o mês anterior, exibindo:

- Total de gastos do mês atual
- Total de gastos do mês anterior  
- Diferença em valor (R$)
- Variação percentual (%)
- Tendência: aumento 📈, redução 📉 ou estável ➡️
- Insight contextualizado baseado na tendência

---

## 🎯 Requisitos Implementados

✅ **Calcular total de despesas por mês** - Método `obterTotalMes()` no Model Despesa  
✅ **Comparar mês atual com anterior** - Método `obterComparativoMeses()` no Model Despesa  
✅ **Exibir comparativo no dashboard** - Seção adicionada na view `despesa_form.php`  
✅ **Aplicar estilos responsivos** - CSS adicionado em `style.css`  
✅ **Gerar insights** - Mensagens contextualizadas baseadas na tendência  
✅ **Testes unitários** - Testes em `tests/Models/DespesaModelTest.php`  

---

## 🔧 Implementação Técnica

### 1. Model Despesa — Novos Métodos

#### `obterTotalMes(string $mes): float`

Calcula o total de despesas para um mês específico.

```php

public function obterTotalMes(string $mes): float {
    if ($this->userId === null) return 0.0;

    $stmt = $this->connection->prepare(
        'SELECT SUM(valor) AS total_mes
         FROM despesas
         WHERE usuario_id = :usuario_id 
         AND deletado_em IS NULL
         AND DATE_FORMAT(data, "%Y-%m") = :mes'
    );

    $stmt->execute(['usuario_id' => $this->userId, 'mes' => $mes]);
    $result = $stmt->fetch();

    return (float) ($result['total_mes'] ?? 0.0);
}
```

#### `obterComparativoMeses(): array`

Retorna o comparativo completo entre o mês atual e anterior.

```php

public function obterComparativoMeses(): array {
    if ($this->userId === null) return [];

    $dataAtual = new \DateTime();
    $mesAtual = $dataAtual->format('Y-m');
    
    $mesAnterior = new \DateTime('first day of last month');
    $mesAnterior = $mesAnterior->format('Y-m');

    $totalMesAtual = $this->obterTotalMes($mesAtual);
    $totalMesAnterior = $this->obterTotalMes($mesAnterior);

    $diferenca = $totalMesAtual - $totalMesAnterior;
    $percentual = $totalMesAnterior > 0 ? ($diferenca / $totalMesAnterior) * 100 : 0;

    return [
        'mes_atual' => $mesAtual,
        'mes_anterior' => $mesAnterior,
        'total_mes_atual' => $totalMesAtual,
        'total_mes_anterior' => $totalMesAnterior,
        'diferenca' => $diferenca,
        'percentual' => $percentual,
        'aumentou' => $diferenca > 0,
        'tendencia' => $diferenca > 0 ? 'aumento' : ($diferenca < 0 ? 'reducao' : 'estavel')
    ];
}
```

### 2. Controller — Dados Preparados

No `DespesaController.php`, o método `create()` agora prepara os dados:

```php

$comparativoMeses = $model->obterComparativoMeses();
```

Esses dados são passados à view `despesa_form.php`.

### 3. View — Renderização do Comparativo

A seção é adicionada ao dashboard apenas quando o filtro de mês é "Todos os Meses" ou mês atual:

```html

<?php if($mesFiltro === date('Y-m') || $mesFiltro === 'todos'): 
    $comp = $comparativoMeses;
    $mesAtualLabel = $mesesPt[substr($comp['mes_atual'], 5, 2)] . ' ' . substr($comp['mes_atual'], 0, 4);
    $mesAnteriorLabel = $mesesPt[substr($comp['mes_anterior'], 5, 2)] . ' ' . substr($comp['mes_anterior'], 0, 4);
?>
<section class="comparison-container">
 
</section>
<?php endif; ?>
```

### 4. Estilos CSS

Adicionados em `public/assets/style.css`:

- `.comparison-container` — Container principal
- `.comparison-grid` — Grade com mês anterior e atual
- `.comparison-card` — Card individual (anterior/atual)
- `.comparison-result` — Resultado com diferença e percentual
- `.comparison-insight` — Mensagem contextualizada
- Tema escuro suportado automaticamente
- Responsividade para mobile (max-width: 768px)

---

## 📱 Interface do Usuário

### Layout Desktop

```
┌────────────────────────────────────────────────────────┐
│ 📊 Comparativo Mensal                                  │
│ Maio 2026 vs Junho 2026                               │
├────────────────────────────────────────────────────────┤
│  Mês Anterior  →  Mês Atual                          │
│  R$ 5.250,00       R$ 5.890,50                         │
│  Maio 2026         Junho 2026                          │
├────────────────────────────────────────────────────────┤
│ 📈  +R$ 640,50                                          │
│     ↑ 12,2%                                             │
├────────────────────────────────────────────────────────┤
│ ⚠️ Seus gastos aumentaram comparado ao mês anterior.  │
│    Considere revisar sua rotina de gastos.            │
└────────────────────────────────────────────────────────┘
```

### Layout Mobile

Adapta-se para coluna única com espaçamento apropriado.

---

## 🎨 Interpretação de Tendências

### 📈 Aumento
- **Quando:** `$diferenca > 0`
- **Cor:** Vermelho (#ef4444)
- **Ícone:** 📈
- **Mensagem:** "Seus gastos aumentaram comparado ao mês anterior. Considere revisar sua rotina de gastos."

### 📉 Redução
- **Quando:** `$diferenca < 0`
- **Cor:** Verde (#10b981)
- **Ícone:** 📉
- **Mensagem:** "Parabéns! Você gastou menos que o mês anterior. Continue assim!"

### ➡️ Estável
- **Quando:** `$diferenca === 0`
- **Cor:** Âmbar (#f59e0b)
- **Ícone:** ➡️
- **Mensagem:** "Seus gastos se mantiveram estáveis em relação ao mês anterior."

---

## 🧪 Testes

Arquivo: `tests/Models/DespesaModelTest.php`

### Casos de Teste Implementados

1. **testObterTotalMes** — Valida o cálculo do total para um mês
2. **testObterComparativoMeses** — Valida a estrutura completa do comparativo
3. **testObterComparativoComUserIdNulo** — Valida retorno com userId nulo
4. **testPercentualVariacaoComparativo** — Valida cálculo do percentual

---

## 📊 Estrutura de Dados Retornada

```php
[
    'mes_atual' => '2026-06',                    // string (YYYY-MM)
    'mes_anterior' => '2026-05',                 // string (YYYY-MM)
    'total_mes_atual' => 5890.50,                // float
    'total_mes_anterior' => 5250.00,             // float
    'diferenca' => 640.50,                       // float (positiva ou negativa)
    'percentual' => 12.19,                       // float (com até 2 casas decimais)
    'aumentou' => true,                          // boolean
    'tendencia' => 'aumento'                     // string ('aumento'|'reducao'|'estavel')
]
```

---

## 🚀 Como Usar

### Para Usuários Finais

1. Acesse o dashboard do Controle Financeiro
2. O comparativo aparece automaticamente logo após a seção de orçamento
3. Compare seus gastos do mês anterior com o atual
4. Leia o insight fornecido e ajuste sua rotina se necessário

### Para Desenvolvedores

```php
// Obter comparativo
$despesaModel = new Despesa($userId);
$comparativo = $despesaModel->obterComparativoMeses();

// Acessar dados individuais
$gastoAtual = $comparativo['total_mes_atual'];     // float
$gastoAnterior = $comparativo['total_mes_anterior']; // float
$percentual = $comparativo['percentual'];           // float
$tendencia = $comparativo['tendencia'];             // string
```

---

## 📝 Notas Técnicas

- **Isolamento de usuário:** Cada usuário vê apenas seus dados
- **Cálculo de mês:** Usa a data atual do sistema
- **Soft delete:** Apenas despesas não deletadas são consideradas
- **Performance:** Usa operações SQL agregadas (SUM)
- **Segurança:** Validação de userId em todos os métodos
- **Responsividade:** Funciona perfeitamente em desktop e mobile

---

## ✨ Melhorias Futuras

- [ ] Exportar comparativo em PDF
- [ ] Gráfico visual da evolução (últimos 12 meses)
- [ ] Notificação por email se gastos ultrapassarem limite
- [ ] Previsão de gastos para próximo mês
- [ ] Comparativo por categoria
- [ ] API REST endpoint `/api.php?route=comparativo` para integração mobile

---

## 📅 Data de Implementação

- **Implementação:** 15 de junho de 2026
- **Status:** ✅ Concluído
- **Testes:** ✅ Implementados
- **Documentação:** ✅ Completa
