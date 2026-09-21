<h1 class="titulo-pagina">Finalizar compra</h1>

<div class="carrinho">
  <form class="formulario checkout__form" action="<?= e(url('pedido', 'confirmar')) ?>" method="post" novalidate>
    <?= csrfCampo() ?>
    <h2>Endereço de entrega</h2>
    <p class="formulario__intro">Preenchido com o endereço do seu cadastro. Você pode alterar só para este pedido.</p>

    <?php if ($erro): ?><div class="aviso aviso--erro" role="alert"><?= e($erro) ?></div><?php endif; ?>

    <div class="campo">
      <label for="endereco">Endereço</label>
      <textarea id="endereco" name="endereco" rows="4" maxlength="255" required><?= e($endereco) ?></textarea>
    </div>

    <p class="checkout__cliente">Pedido em nome de <strong><?= e($cliente['nome']) ?></strong> (CPF <?= e(formatarCpf($cliente['cpf'])) ?>).</p>

    <button type="submit" class="botao botao--primario botao--cheio">Confirmar pedido</button>
  </form>

  <aside class="resumo" aria-label="Itens do pedido">
    <h2>Seu pedido</h2>
    <ul class="resumo__itens">
      <?php foreach ($carrinho->itens() as $item): ?>
        <li>
          <span><?= (int) $item['quantidade'] ?> × <?= e($item['produto_nome']) ?>
            <small><?= e($item['tamanho']) ?>, <?= e($item['cor']) ?></small></span>
          <span><?= e(moeda($carrinho->subtotal($item))) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <dl>
      <div class="resumo__total"><dt>Total</dt><dd><?= e(moeda($carrinho->total())) ?></dd></div>
    </dl>
    <a class="link" href="<?= e(url('carrinho', 'index')) ?>">Voltar ao carrinho</a>
  </aside>
</div>
