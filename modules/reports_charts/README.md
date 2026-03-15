# Reports Charts Module

Módulo para exibir gráficos interativos na página de relatórios de faturas do Perfex CRM.

## Funcionalidades

- **4 tipos de gráfico**: Barras agrupadas, Barras empilhadas, Pizza, Gantt (timeline)
- **Cards de resumo**: Total gerado, Pago, Pendente, Parcialmente pago (valores + contagem de faturas)
- **Seleção de período**: Este mês, Mês passado, Este ano, Ano passado, Personalizado
- **Modal de detalhes**: Clique em qualquer barra/fatia para ver a lista de faturas do período
  - Nome do cliente, número da proposta/fatura, status
  - Botão para abrir a fatura em nova aba
- **Totais do modal**: subtotal de cada status dentro do período selecionado

## Instalação

### 1. Copiar arquivos

```
application/
  modules/
    reports_charts/          ← copiar esta pasta aqui
      assets/
        css/reports_charts.css
        js/reports_charts.js
      views/
        charts_view.php
      reports_charts_module.php
      README.md
```

### 2. Incluir o CSS e JS na view de relatórios

No arquivo `application/views/admin/reports/invoices.php` (ou similar), adicione no `<head>`:

```html
<link rel="stylesheet" href="<?php echo base_url('modules/reports_charts/assets/css/reports_charts.css'); ?>">
```

E antes do `</body>`:

```html
<script src="<?php echo base_url('modules/reports_charts/assets/js/reports_charts.js'); ?>"></script>
```

### 3. Renderizar o módulo de gráficos

Na mesma view, antes ou depois da tabela existente:

```php
<?php
// Opção A — via classe helper
require_once APPPATH . 'modules/reports_charts/reports_charts_module.php';
Reports_charts_module::render();

// Opção B — incluir a view diretamente
$this->load->view('reports_charts/charts_view');
?>
```

### 4. Verificar o CSRF token

O módulo usa `$this->security->get_csrf_token_name()` e `get_csrf_hash()` que são métodos padrão do CodeIgniter 3/Perfex CRM. Se o seu setup usa outro mecanismo de CSRF, ajuste as variáveis em `charts_view.php`:

```php
window.ReportsChartsConfig = {
    csrfName: '<?php echo $this->security->get_csrf_token_name(); ?>',
    csrfHash: '<?php echo $this->security->get_csrf_hash(); ?>',
    ...
};
```

## Estrutura do payload esperado da API

`POST /admin/reports/invoices_report`

```json
{
  "draw": 1,
  "aaData": [
    ["<a href='...'>INV-000135</a>", "<a href='...'>Cliente</a>", "2026",
     "2026-03-14", "2026-04-13", "$1200.00", "$1279.56", "$79.56",
     "$79.56", "$0.00", "$0.00", "$0.00", "$1279.56",
     "<span class='label label-danger invoice-status-1'>Não pago</span>"],
    ...
  ],
  "sums": {
    "total": "$7,725.77",
    "subtotal": "$7,320.00",
    "amount_open": "$4,712.78"
  }
}
```

### Mapeamento das colunas

| Índice | Campo          |
|--------|----------------|
| 0      | Número da fatura (link) |
| 1      | Nome do cliente (link) |
| 2      | Ano |
| 3      | Data de emissão |
| 4      | Data de vencimento |
| 5      | Subtotal |
| 6      | Total |
| 7–11   | Impostos / descontos / ajustes |
| 12     | Valor em aberto |
| 13     | Status (HTML com classe `invoice-status-N`) |

### Status

| Classe             | Status          |
|--------------------|-----------------|
| `invoice-status-1` | Não pago        |
| `invoice-status-2` | Pago            |
| `invoice-status-3` | Parcialmente Pago |

## Customização

### Cores
Edite o objeto `COLORS` no início de `reports_charts.js`.

### Moeda
Definida por `ReportsChartsConfig.currency` (passado via PHP, usa `get_base_currency()->symbol`).

### Internacionalização
Os textos dos labels são passados via `ReportsChartsConfig.labels` no `charts_view.php`.
