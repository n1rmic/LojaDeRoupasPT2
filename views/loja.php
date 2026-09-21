<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Loja Virtual - Vitrine Client-Side</title>
  <link rel="stylesheet" href="/loja_roupas/public/assets/css/loja.css">
</head>
<body>

  <header class="header-loja">
    <h1>🛍️ Loja Geek & Cosplay</h1>
    <div class="nav-actions">
      <button class="btn-icon" id="btn-favs">❤️ Favoritos <span class="badge-count" id="count-favs">0</span></button>
      <button class="btn-icon" id="btn-cart">🛒 Carrinho <span class="badge-count" id="count-cart">0</span></button>
      <button class="btn-icon" id="btn-account">👤 Minha Conta</button>
    </div>
  </header>

  <main class="grid-produtos" id="grid-produtos">
    <p>Carregando produtos...</p>
  </main>

  <!-- Modal de Cadastro / Login -->
  <div class="modal" id="modal-cliente">
    <div class="modal-content">
      <h2>Cadastro de Cliente</h2>
      <form id="form-cadastro">
        <input type="text" id="cad-nome" placeholder="Seu Nome Completo" required>
        <input type="email" id="cad-email" placeholder="Seu E-mail" required>
        <input type="password" id="cad-senha" placeholder="Sua Senha" required>
        <button type="submit" class="btn-add">Cadastrar</button>
        <button type="button" class="btn-icon" style="width: 100%; margin-top: 10px;" id="close-modal">Fechar</button>
      </form>
    </div>
  </div>

  <script src="/loja_roupas/public/assets/js/loja.js"></script>
</body>
</html>