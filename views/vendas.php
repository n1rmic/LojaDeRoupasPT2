<?php
$nomeUser = $_SESSION['nome'] ?? 'Usuário';
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Vendas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/loja_roupas/public/assets/css/style.css">
</head>
<body>
<div class="container">
  <div class="topbar">
    <div class="brand">
      <div class="badge"></div>
      <div>
        <h1>Vendas</h1>
        <small>Registro de vendas do balcão</small>
      </div>
    </div>
    <div class="pill">
      Olá, <strong><?= htmlspecialchars($nomeUser) ?></strong>
      • <a href="/loja_roupas/index.php?controller=auth&action=logout">Sair</a>
    </div>
  </div>
 
  <div class="card">
    <h2>Nova Venda</h2>
 
    <form method="post" action="index.php?controller=venda&action=salvar">
      <label>Cliente</label>
      <select class="input" name="cliente_id" required>
        <option value="">Selecione...</option>
        <?php foreach ($clientes as $cl): ?>
          <option value="<?= (int)$cl['id'] ?>"><?= htmlspecialchars($cl['nome']) ?></option>
        <?php endforeach; ?>
      </select>
 
      <h3 style="margin-top:18px;">Itens vendidos</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Variação (SKU)</th>
            <th style="width:120px;">Quantidade</th>
            <th style="width:150px;">Preço Unitário (R$)</th>
          </tr>
        </thead>
        <tbody>
          <?php for ($linha = 0; $linha < 5; $linha++): ?>
          <tr>
            <td>
              <select class="input" name="variacao_id[]" data-linha="<?= $linha ?>">
                <option value="">-- não usar esta linha --</option>
                <?php foreach ($variacoes as $v): ?>
                  <option value="<?= (int)$v['id'] ?>" data-preco="<?= (float)$v['preco'] ?>">
                    <?= htmlspecialchars($v['produto_nome']) ?> —
                    <?= htmlspecialchars($v['sku']) ?>
                    (<?= htmlspecialchars($v['tamanho']) ?>/<?= htmlspecialchars($v['cor']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input class="input" type="number" min="0" name="quantidade[]" value="0"></td>
            <td><input class="input" type="text" name="preco_unitario[]" value="0,00"></td>
          </tr>
          <?php endfor; ?>
        </tbody>
      </table>
 
      <p class="muted" style="font-size:13px; margin-top:8px;">
        Dica: o preço é sugerido a partir do cadastro da variação, mas pode ser ajustado
        manualmente (ex.: promoção pontual).
      </p>
 
      <div class="actions" style="margin-top:14px;">
        <button class="btn btn-primary" type="submit">Finalizar Venda</button>
      </div>
    </form>
  </div>
  <br>
    <br>
    <br>
  <div class="card">
   
    <h2>Vendas Recentes</h2>

 <table class="table">
      <thead>
        <tr>
          <th>ID</th><th>Cliente</th><th>Vendedor</th><th>Data</th><th>Valor Total</th>
          <th style="width:140px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($vendas as $v): ?>
        <tr>
          <td>#<?= (int)$v['id'] ?></td>
          <td><?= htmlspecialchars($v['cliente_nome']) ?></td>
          <td><?= htmlspecialchars($v['vendedor_nome']) ?></td>
          <td><?= htmlspecialchars($v['data']) ?></td>
          <td>R$ <?= number_format((float)$v['valor_total'], 2, ',', '.') ?></td>
          <td>
  <a class="btn" href="index.php?controller=venda&action=emitirNota&venda_id=<?= (int)$v['id'] ?>">
    Emitir NF
  </a>
  <br>
  <br>
  <a class="btn" href="index.php?controller=venda&action=verNota&venda_id=<?= (int)$v['id'] ?>">
    Ver / Imprimir NF
  </a>
</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>




    <div style="margin-top:14px;">
      <a class="btn" href="/loja_roupas/index.php?controller=auth&action=dashboard">Voltar ao Dashboard</a>
    </div>
  </div>
</div>
 
<script>
// Preenche automaticamente o preço sugerido ao escolher uma variação.
document.querySelectorAll('select[name="variacao_id[]"]').forEach(function (select) {
  select.addEventListener('change', function () {
    const opcao = this.options[this.selectedIndex];
    const preco = opcao.getAttribute('data-preco');
    const linha = this.closest('tr');
    const inputPreco = linha.querySelector('input[name="preco_unitario[]"]');
    if (preco) {
      inputPreco.value = parseFloat(preco).toFixed(2).replace('.', ',');
    }
  });
});
</script>
</body>
</html>
