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
| Checkout / Confirmar | `controller=pedido&action=checkout`, `confirmar` |
| Confirmação / Meus pedidos | `controller=pedido&action=confirmacao&id=N`, `meusPedidos` |

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
- Segurança básica: prepared statements em todas as consultas, `password_hash`/`password_verify`, token CSRF em todo POST,
  escape de saída (`e()`), `session_regenerate_id` no login. As pastas `config`, `models`, `controllers`, `views` e `sql`
  têm `.htaccess` bloqueando acesso direto pelo navegador.
- Não há tela do vendedor para mudar o status do pedido (pendente → pago → enviado…) — ficou fora do escopo pedido.

## Arquivos

```
index.php                      roteador (modelo)
config/helpers.php             e(), moeda(), validarCpf(), CSRF, flash, imagemProduto()...
controllers/                   Loja, Carrinho, Cliente, Pedido (+ SiteBaseController)
models/                        Vitrine, Carrinho, ClienteSite, Pedido
views/site/                    todas as telas do cliente
public/assets/css/loja.css     estilos
public/assets/js/loja.js       seletor de variação, máscaras de CPF/telefone
public/assets/img/placeholder.svg
sql/cliente_site.sql           cliente_site + pedido + pedido_item
```
