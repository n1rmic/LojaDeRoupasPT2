<?php
// Espera receber $nota (array da nota_fiscal_venda) e $venda (array da venda)
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <title>Nota Fiscal de Venda - Nº <?= htmlspecialchars($nota['numero']) ?></title>
    <link rel="stylesheet" href="/loja_roupas/public/assets/css/nota.css">
    <link rel="stylesheet" href="nota_fiscal.css">
</head>

<body>


    <div class="nf-container">

        <!-- CABEÇALHO DA NOTA -->
        <header class="nf-cabecalho">
            <div class="nf-emitente">
                <h1 class="nf-emitente-nome">LOJA COSPLAY LTDA</h1>
                <p class="nf-emitente-documento">CNPJ: 00.000.000/0001-00</p>
                <p class="nf-emitente-endereco">Rua Principal, 123 - Centro - Cidade/UF</p>
            </div>

            <div class="nf-documento-info">
                <h2 class="nf-documento-titulo">NOTA FISCAL</h2>
                <div class="nf-documento-dados">
                    <p class="nf-dado-linha"><strong>NÚMERO:</strong> <?= htmlspecialchars($nota['numero']) ?></p>
                    <p class="nf-dado-linha"><strong>SÉRIE:</strong> <?= htmlspecialchars($nota['serie']) ?></p>
                    <p class="nf-dado-linha"><strong>MODELO:</strong> <?= htmlspecialchars($nota['modelo']) ?></p>
                </div>
            </div>
        </header>

        <!-- DADOS DE EMISSÃO (DATA E HORA) -->
        <section class="nf-emissao">
            <div class="nf-emissao-bloco">
                <span class="nf-emissao-label">DATA DE EMISSÃO</span>
                <span class="nf-emissao-valor"><?= htmlspecialchars($nota['data_emissao']) ?></span>
            </div>
            <div class="nf-emissao-bloco">
                <span class="nf-emissao-label">HORA DE EMISSÃO</span>
                <!-- Puxa a hora definida ou gera o horário atual caso não configurado -->
                <span class="nf-emissao-valor"><?= htmlspecialchars($nota['hora_emissao'] ?? date('H:i:s')) ?></span>
            </div>
            <div class="nf-emissao-bloco">
                <span class="nf-emissao-label">REFERÊNCIA VENDA</span>
                <span class="nf-emissao-valor">#<?= (int) $venda['id'] ?></span>
            </div>
        </section>

        <!-- CORPO / PRODUTOS VENDIDOS -->
        <main class="nf-conteudo">
            <table class="nf-tabela-produtos">
                <thead>
                    <tr>
                        <th class="nf-th-codigo">CÓDIGO</th>
                        <th class="nf-th-descricao">DESCRIÇÃO DO PRODUTO VENDIDO</th>
                        <th class="nf-th-qtd">QTD</th>
                        <th class="nf-th-valor-uni">V. UNITÁRIO</th>
                        <th class="nf-th-valor-tot">V. TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($venda['itens']) && is_array($venda['itens'])): ?>
                        <!-- Loop se a venda possuir sub-itens estruturados -->
                        <?php foreach ($venda['itens'] as $item): ?>
                            <tr>
                                <td class="nf-td-codigo">#<?= htmlspecialchars($item['id'] ?? '') ?></td>
                                <td class="nf-td-descricao"><?= htmlspecialchars($item['nome'] ?? $item['descricao']) ?></td>
                                <td class="nf-td-qtd"><?= (int)($item['quantidade'] ?? 1) ?></td>
                                <td class="nf-td-valor-uni">R$ <?= number_format((float)$item['valor_unitario'], 2, ',', '.') ?></td>
                                <td class="nf-td-valor-tot">R$ <?= number_format((float)$item['valor_total'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Puxa o nome do que foi vendido diretamente da raiz se for item único -->
                        <tr>
                            <td class="nf-td-codigo">#<?= (int)$venda['id'] ?></td>
                            <td class="nf-td-descricao"><?= htmlspecialchars($venda['produto_nome'] ?? 'Produto Geral / Ref. Venda #' . $venda['id']) ?></td>
                            <td class="nf-td-qtd"><?= (int)($venda['quantidade'] ?? 1) ?></td>
                            <td class="nf-td-valor-uni">R$ <?= number_format((float)($venda['valor_unitario'] ?? $nota['valor_total']), 2, ',', '.') ?></td>
                            <td class="nf-td-valor-tot">R$ <?= number_format((float)$nota['valor_total'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>

        <!-- TOTAIS DA NOTA -->
        <footer class="nf-rodape">
            <div class="nf-total-bloco">
                <span class="nf-total-label">VALOR TOTAL DA NOTA</span>
                <span class="nf-total-valor">R$ <?= number_format((float)$nota['valor_total'], 2, ',', '.') ?></span>
            </div>
        </footer>

        <!-- BOTÕES DE AÇÃO -->
        <div class="nf-botoes no-print">
            <button class="nf-btn-imprimir" onclick="window.print()">Imprimir Nota Fiscal</button>
        </div>

    </div>

</body>

</html>