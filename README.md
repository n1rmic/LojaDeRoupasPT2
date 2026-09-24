# Loja de Cosplay — lado do CLIENTE

Vitrine, carrinho, cadastro/login, checkout com pagamento simulado, geração de NF-e em PDF e histórico de pedidos,
usando o mesmo banco `loja_roupas` do sistema do vendedor.

## Requisitos

- PHP 8+ com as extensões `pdo_mysql`, `mbstring` e `curl` (todas vêm ativas no XAMPP por padrão).
  - `curl` é usada pelo `CepController` para consultar a API pública do ViaCEP.
- Tabelas `produto`, `variacao` e `estoque` em **InnoDB** (necessário para a chave estrangeira de `pedido_item` e para a
  transação do pedido).
- Composer com as dependências já instaladas em `vendor/` (usa `dompdf/dompdf` para gerar PDFs).

## Instalação (XAMPP), passo a passo

1. **Copie** as pastas/arquivos deste projeto para dentro do seu projeto existente (mesma estrutura de pastas).
   Não sobrescreva `config/db.php` — o arquivo existente é reaproveitado.
2. **index.php:** se o seu já tem a verificação de login do vendedor, **não substitua**. Copie para o seu apenas
   o `require_once __DIR__ . '/config/helpers.php';`, a lista `$controllersLoja` e o trecho que carrega o controller
   (o `index.php` deste projeto serve de modelo). Sem controller na URL, ele abre a loja.
3. No phpMyAdmin, com `loja_roupas.sql` já importado, importe **`sql/cliente_site.sql`**
   (cria `cliente_site`, `pedido` e `pedido_item`; não altera nenhuma tabela existente).
   **Nenhum outro comando SQL é necessário** — todos os recursos abaixo (CEP, checkout, pagamento, NF-e) reaproveitam
   essas mesmas tabelas, sem colunas novas.
4. Ative o Apache e o MySQL no XAMPP e acesse:
   `http://localhost/SUA_PASTA/index.php?controller=loja&action=index`

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

---

## Recursos implementados — o que cada um precisa

Cada item abaixo lista os **controllers/models/views envolvidos** e se exige algum comando SQL adicional.

### 1. Vitrine, carrinho, cadastro e login (base do projeto)

- **Controllers:** `LojaController`, `CarrinhoController`, `ClienteController`, `SiteBaseController`.
- **Models:** `Vitrine`, `Carrinho`, `ClienteSite`.
- **SQL:** o de `sql/cliente_site.sql` (passo 3 da instalação). Nada além disso.

### 2. Busca de CEP com ViaCEP

- **O que faz:** consulta `https://viacep.com.br/ws/{cep}/json/` via `curl` e devolve rua, bairro, cidade e UF em JSON.
- **Controller:** `controllers/CepController.php` (rota `cep&action=buscar`) — já existia no projeto, foi só reaproveitado.
- **Onde é usado:** `views/site/checkout.php` (endereço de entrega) e `views/site/meus_dados.php` (campo de cadastro),
  ambos com um `<script>` que faz `fetch()` para essa rota ao digitar 8 dígitos de CEP.
- **SQL:** **nenhum.** O endereço continua salvo como texto único na coluna `endereco` (já existente em
  `cliente_site` e em `pedido.endereco_entrega`) — o CEP só ajuda a **montar** esse texto no formulário, não muda o
  banco.

### 3. Checkout em duas etapas (endereço → forma de pagamento)

- **O que faz:** separa o antigo fluxo de um passo só em: (1) `checkout` — endereço de entrega, (2) `pagamento` —
  escolha entre cartão/Pix, (3) `finalizar` — cria o pedido de fato. A antiga action `confirmar` foi removida.
- **Controller:** `controllers/PedidoController.php` (métodos `checkout()`, `pagamento()`, `finalizar()`).
- **Views:** `views/site/checkout.php`, `views/site/pagamento.php`.
- **Estado entre as etapas:** o endereço validado fica em `$_SESSION['checkout_endereco']` até `finalizar()` gravar o
  pedido e limpar a sessão.
- **Reaproveitar endereço cadastrado:** se o cliente já tem `endereco` salvo, o checkout mostra um rádio "Usar
  endereço cadastrado" (padrão) ou "Usar outro endereço para este pedido" — os campos de CEP/rua/número só ficam
  visíveis e obrigatórios na segunda opção.
- **SQL:** nenhum.

### 4. Forma de pagamento (cartão ou Pix) — ⚠️ SIMULADO

- **O que faz:** exibe dois métodos de pagamento. Selecionando **cartão**, aparecem campos de número, nome, validade
  e CVV. Selecionando **Pix**, aparece um QR Code.
- **View:** `views/site/pagamento.php`. **CSS:** classes `.pgto-*` no final de `public/assets/css/loja.css`.
- **⚠️ Importante — não é um pagamento de verdade:**
  - Os campos do cartão são validados **só quanto a preenchimento** (nenhum campo vazio); não há Luhn, não há
    consulta a bandeira, não há gateway de pagamento e **nada desses dados é salvo no banco**.
  - O Pix não gera cobrança real nenhuma — é só uma imagem de QR Code fixa.
  - Isso foi implementado assim de propósito, para fins didáticos/demonstração da tela — se este projeto for usado
    para vender de verdade, é obrigatório trocar isso por um gateway real (Stripe, Mercado Pago, PagSeguro etc.)
    antes de aceitar qualquer pagamento de um cliente real.
- **SQL:** nenhum (o método escolhido não é nem gravado — o pedido só registra o endereço e os itens).

### 5. QR Code do Pix — 🎉 contém uma brincadeira

- **O que é:** `public/assets/img/qrcode-pix.svg`, um QR Code **de verdade** (legível por qualquer leitor de QR),
  mas que **não aponta para um Pix** — aponta para um link de vídeo do YouTube, a pedido de quem encomendou o projeto.
- **Como foi gerado:** com a biblioteca Python `qrcode` (`pip install qrcode`), **fora do projeto PHP** — não existe
  nenhum script dentro deste repositório que gere isso automaticamente. Se quiser trocar o conteúdo do QR (por
  exemplo, apontar para uma chave Pix de verdade), é preciso gerar um novo arquivo SVG por fora e substituir esse
  arquivo — não há como editar o QR pela interface do site.
- **Recomendação:** troque este arquivo antes de qualquer demonstração séria do projeto (para professor, banca,
  cliente etc.), para não escanear sem saber o que vai abrir.

### 6. NF-e do pedido em PDF (para o cliente baixar)

- **O que faz:** gera um PDF da nota fiscal de um pedido do próprio cliente, para download.
- **Controller:** `PedidoController::baixarNota()` (rota `pedido&action=baixarNota&id=N`).
- **View/template do PDF:** `views/nota_fiscal_pedido.php` (HTML simples, convertido em PDF pela biblioteca **Dompdf**,
  a mesma já usada no relatório do vendedor em `RelatorioController::exportarPdf()`).
- **Onde baixar:** botão "Baixar NF-e" na tela de confirmação do pedido (`views/site/confirmacao.php`) e em cada
  pedido da lista "Meus pedidos" (`views/site/meus_pedidos.php`).
- **SQL:** nenhum. Diferente da NF do vendedor (tabela `nota_fiscal_venda`, com numeração sequencial oficial gravada
  no banco via `models/NotaFiscal.php`), aqui o PDF é **montado na hora**, direto dos dados do pedido — não existe
  tabela `nota_fiscal_pedido`. O "número" exibido no PDF é só o ID do pedido preenchido com zeros à esquerda
  (ex.: `#000042`), não é uma numeração fiscal oficial.

---

## Decisões técnicas importantes

- **Sessão do cliente separada da do vendedor:** `$_SESSION['cliente_id']` (guarda o CPF) e `['cliente_nome']`; o
  carrinho fica em `$_SESSION['carrinho']`. Sair da conta do cliente não encerra a sessão do vendedor.
- **Pedido não usa `venda`/`venda_item`** (PDV do vendedor): usa `pedido` e `pedido_item`, com o endereço de entrega
  gravado no pedido.
- **Estoque:** ao finalizar, em uma transação: trava a linha de `estoque`, confere a quantidade, grava o pedido, dá
  baixa e registra a saída em `movimento_estoque`. Se qualquer passo falhar, nada é gravado. O preço do pedido é
  sempre lido do banco, nunca da sessão.
- **`movimento_estoque`:** o código grava `variacao_id, tipo='saida', quantidade, origem='venda', origem_id=id do
  pedido`. Se as colunas da sua tabela tiverem outros nomes, ajuste **só** o método `registrarSaidaEstoque()` em
  `models/Pedido.php`.
- **`produto&action=listarJson`:** este projeto não altera o controller do vendedor. Use `loja&action=produtosJson`
  ou, se quiser corrigir o antigo, remova `p.imagem` do SELECT.
- Segurança básica: prepared statements em todas as consultas, `password_hash`/`password_verify`, token CSRF em todo
  POST, escape de saída (`e()`), `session_regenerate_id` no login. As pastas `config`, `models`, `controllers`,
  `views` e `sql` têm `.htaccess` bloqueando acesso direto pelo navegador.
- Não há tela do vendedor para mudar o status do pedido (pendente → pago → enviado…) — ficou fora do escopo pedido.

## Arquivos

```
index.php                          roteador (modelo)
config/helpers.php                 e(), moeda(), validarCpf(), formatarCep(), CSRF, flash, imagemProduto()...
controllers/
  LojaController.php                vitrine
  CarrinhoController.php            carrinho
  ClienteController.php             cadastro, login, meus dados
  CepController.php                 consulta ViaCEP (curl) -> JSON
  PedidoController.php              checkout, pagamento, finalizar, confirmação, meus pedidos, baixar NF-e
  SiteBaseController.php            base comum (login, render, CSRF) dos controllers do cliente
models/
  Vitrine.php, Carrinho.php, ClienteSite.php, Pedido.php
views/site/                        todas as telas do cliente (inclui checkout.php, pagamento.php, meus_dados.php)
views/nota_fiscal_pedido.php        template HTML da NF-e do pedido, convertido em PDF pelo Dompdf
public/assets/css/loja.css          estilos (inclui os blocos de pagamento e opções de endereço no final do arquivo)
public/assets/js/loja.js            seletor de variação, máscaras de CPF/telefone
public/assets/img/placeholder.svg
public/assets/img/qrcode-pix.svg    QR Code exibido no pagamento via Pix (ver aviso na seção 5 acima)
sql/cliente_site.sql                cliente_site + pedido + pedido_item (único SQL necessário)
```