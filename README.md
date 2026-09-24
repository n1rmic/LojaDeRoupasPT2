# Loja de Cosplay — lado do CLIENTE

Vitrine, carrinho, cadastro/login e pedidos, usando o mesmo banco `loja_roupas` do sistema do vendedor.

## Como rodar (XAMPP)

1. **Copie** as pastas/arquivos deste projeto para dentro do seu projeto existente (mesma estrutura de pastas).
   Não sobrescreva `config/db.php` — o arquivo existente é reaproveitado.
2. **index.php:** se o seu já tem a verificação de login do vendedor, **não substitua**. Copie para o seu apenas
   o `require_once __DIR__ . '/config/helpers.php';`, a lista `$controllersLoja` e o trecho que carrega o controller
   (o `index.php` deste projeto serve de modelo). Sem controller na URL, ele abre a loja.
3. No phpMyAdmin, com `loja_roupas.sql` já importado, importe **`sql/cliente_site.sql`**
   (cria `cliente_site`, `pedido` e `pedido_item`; não altera nenhuma tabela existente).
4. Ative o Apache e o MySQL no XAMPP e acesse:
   `http://localhost/SUA_PASTA/index.php?controller=loja&action=index`

Requisitos: PHP 8+ com `pdo_mysql` e `mbstring` (ambos vêm ativos no XAMPP). Tabelas `produto`, `variacao`, `estoque` em InnoDB
(necessário para a chave estrangeira de `pedido_item` e para a transação do pedido).

## Imagens dos produtos

Sem coluna no banco: coloque o arquivo em `public/uploads/produtos/{id_do_produto}.jpg` (ou `.png`, `.webp`).
Sem arquivo, aparece `public/assets/img/placeholder.svg`.

## Rotas

| Tela | URL |
|---|---|
| Vitrine (filtro: `categoria_id`, `q`, `pagina`) | `controller=loja&action=index` |
| Produto | `controller=loja&action=produto&id=X` |
| JSON da vitrine | `controller=loja&action=produtosJson` |
| Carrinho | `controller=carrinho&action=index` (POST: `adicionar`, `atualizar`, `remover`) |
| Cadastro / Entrar / Sair | `controller=cliente&action=cadastro`, `login`, `logout` |
| Meus dados / trocar senha | `controller=cliente&action=meusDados`, `alterarSenha` |
| Busca de CEP (ViaCEP, JSON) | `controller=cep&action=buscar&cep=00000000` |
| Checkout (endereço) → Pagamento → Finalizar | `controller=pedido&action=checkout`, `pagamento`, `finalizar` |
| Confirmação / Meus pedidos | `controller=pedido&action=confirmacao&id=N`, `meusPedidos` |
| Baixar NF-e do pedido (PDF) | `controller=pedido&action=baixarNota&id=N` |

## Decisões importantes

- **Sessão do cliente separada da do vendedor:** `$_SESSION['cliente_id']` (guarda o CPF) e `['cliente_nome']`; o carrinho fica em `$_SESSION['carrinho']`.
  Sair da conta do cliente não encerra a sessão do vendedor.
- **Pedido não usa `venda`/`venda_item`** (PDV do vendedor): usa `pedido` e `pedido_item`, com o endereço de entrega gravado no pedido.
- **Estoque:** ao confirmar, em uma transação: trava a linha de `estoque`, confere a quantidade, grava o pedido, dá baixa e
  registra a saída em `movimento_estoque`. Se qualquer passo falhar, nada é gravado.
  O preço do pedido é sempre lido do banco, nunca da sessão.
- **`movimento_estoque`:** o código grava `variacao_id, tipo='saida', quantidade, origem='venda', origem_id=id do pedido`.
  Se as colunas da sua tabela tiverem outros nomes, ajuste **só** o método `registrarSaidaEstoque()` em `models/Pedido.php`.
- **`produto&action=listarJson`:** este projeto não altera o controller do vendedor. Use `loja&action=produtosJson`
  ou, se quiser corrigir o antigo, remova `p.imagem` do SELECT.
- **Checkout em duas etapas (endereço → pagamento):** `pedido&action=checkout` (GET) mostra o endereço; o POST vai para
  `pedido&action=pagamento`, que valida e mostra a forma de pagamento; o POST final vai para `pedido&action=finalizar`,
  que cria o pedido (a antiga action `confirmar` foi substituída por essas duas). O endereço fica temporariamente em
  `$_SESSION['checkout_endereco']` entre as duas etapas.
- **Endereço com CEP (ViaCEP):** o checkout usa a rota `cep&action=buscar` (já existente no projeto, `CepController`)
  para autopreencher rua/bairro/cidade/UF a partir do CEP digitado (JS em `views/site/checkout.php`). O mesmo recurso
  foi adicionado em "Meus dados" (`views/site/meus_dados.php`) para ajudar a preencher o campo `endereco` do cadastro.
  Não há colunas novas no banco: o endereço continua sendo gravado como texto único na coluna `endereco` de
  `cliente_site` (e em `pedido.endereco_entrega`), só que agora composto a partir dos campos estruturados do formulário.
- **Reaproveitar endereço cadastrado no checkout:** se o cliente já tem endereço salvo, o checkout mostra a opção
  "Usar endereço cadastrado" (padrão) ou "Usar outro endereço para este pedido" — só neste segundo caso os campos de
  CEP/rua/número etc. aparecem e são obrigatórios.
- **Forma de pagamento (`views/site/pagamento.php`):** cartão de crédito (com inputs de número, nome, validade e CVV,
  validados apenas quanto a preenchimento — não há gateway real) ou Pix, que exibe um QR Code. O QR Code
  (`public/assets/img/qrcode-pix.svg`) é gerado de verdade (biblioteca Python `qrcode`, fora do projeto PHP) e só serve
  para fins didáticos/demonstração.
- **NF-e do pedido (cliente):** `pedido&action=baixarNota&id=N` gera um PDF (via Dompdf, já usado no relatório do
  vendedor) a partir de `views/nota_fiscal_pedido.php`, com os dados do próprio pedido — sem tabela nova no banco e
  sem numeração sequencial oficial (o "número" exibido é só o ID do pedido com zeros à esquerda). Botão "Baixar NF-e"
  aparece na tela de confirmação do pedido e em cada item de "Meus pedidos".
- Segurança básica: prepared statements em todas as consultas, `password_hash`/`password_verify`, token CSRF em todo POST,
  escape de saída (`e()`), `session_regenerate_id` no login. As pastas `config`, `models`, `controllers`, `views` e `sql`
  têm `.htaccess` bloqueando acesso direto pelo navegador.
- Não há tela do vendedor para mudar o status do pedido (pendente → pago → enviado…) — ficou fora do escopo pedido.

## Arquivos

```
index.php                      roteador (modelo)
config/helpers.php             e(), moeda(), validarCpf(), formatarCep(), CSRF, flash, imagemProduto()...
controllers/                   Loja, Carrinho, Cliente, Pedido, Cep (+ SiteBaseController)
models/                        Vitrine, Carrinho, ClienteSite, Pedido
views/site/                    todas as telas do cliente (inclui pagamento.php)
views/nota_fiscal_pedido.php   template HTML da NF-e do pedido, convertido em PDF pelo Dompdf
public/assets/css/loja.css     estilos (inclui os blocos de pagamento e opções de endereço)
public/assets/js/loja.js       seletor de variação, máscaras de CPF/telefone
public/assets/img/placeholder.svg
public/assets/img/qrcode-pix.svg  QR Code de exemplo exibido no pagamento via Pix
sql/cliente_site.sql           cliente_site + pedido + pedido_item
```