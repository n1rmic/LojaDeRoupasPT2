document.addEventListener('DOMContentLoaded', () => {
  // Estado local sincronizado com LocalStorage
  let carrinho = JSON.parse(localStorage.getItem('carrinho')) || [];
  let favoritos = JSON.parse(localStorage.getItem('favoritos')) || [];

  // Seleção de elementos do DOM
  const gridProdutos = document.getElementById('grid-produtos');
  const countCart = document.getElementById('count-cart');
  const countFavs = document.getElementById('count-favs');
  const modalCliente = document.getElementById('modal-cliente');
  const btnAccount = document.getElementById('btn-account');
  const btnCloseModal = document.getElementById('close-modal');
  const formCadastro = document.getElementById('form-cadastro');

  // Atualiza os contadores no topo e salva no LocalStorage
  function atualizarContadores() {
    const totalItensCarrinho = carrinho.reduce((acc, item) => acc + item.qtd, 0);
    if (countCart) countCart.textContent = totalItensCarrinho;
    if (countFavs) countFavs.textContent = favoritos.length;

    localStorage.setItem('carrinho', JSON.stringify(carrinho));
    localStorage.setItem('favoritos', JSON.stringify(favoritos));
  }

  // Busca a lista de produtos no servidor
  async function carregarProdutos() {
    try {
      const res = await fetch('index.php?controller=produto&action=listarJson');

      if (!res.ok) {
        throw new Error(`Erro na requisição: ${res.status}`);
      }

      const produtos = await res.json();
      gridProdutos.innerHTML = '';

      if (!Array.isArray(produtos) || produtos.length === 0) {
        gridProdutos.innerHTML = '<p>Nenhum produto disponível no momento.</p>';
        return;
      }

      produtos.forEach(prod => {
        const isFav = favoritos.includes(parseInt(prod.id));
        const imgUrl = prod.imagem 
          ? `/loja_roupas/public/uploads/${prod.imagem}` 
          : 'https://via.placeholder.com/200?text=Sem+Imagem';

        const card = document.createElement('div');
        card.className = 'card-produto';
        card.innerHTML = `
          <button class="fav-btn" data-id="${prod.id}">${isFav ? '❤️' : '🤍'}</button>
          <img src="${imgUrl}" alt="${prod.nome}" class="prod-img">
          <div>
            <h3>${prod.nome}</h3>
            <p style="color: var(--txt-muted); font-size: 13px;">SKU: ${prod.sku || 'N/A'}</p>
          </div>
          <div>
            <div class="preco">R$ ${parseFloat(prod.preco).toFixed(2).replace('.', ',')}</div>
            <button class="btn-add" data-id="${prod.id}" data-nome="${prod.nome}" data-preco="${prod.preco}">
              Adicionar ao Carrinho
            </button>
          </div>
        `;
        gridProdutos.appendChild(card);
      });
    } catch (err) {
      console.error(err);
      gridProdutos.innerHTML = '<p>Erro ao carregar os produtos do servidor.</p>';
    }
  }

  // Event Delegation no Grid de Produtos (Favoritar e Adicionar ao Carrinho)
  if (gridProdutos) {
    gridProdutos.addEventListener('click', (e) => {
      // Botão Favoritar
      if (e.target.classList.contains('fav-btn')) {
        const id = parseInt(e.target.dataset.id);
        if (favoritos.includes(id)) {
          favoritos = favoritos.filter(favId => favId !== id);
          e.target.textContent = '🤍';
        } else {
          favoritos.push(id);
          e.target.textContent = '❤️';
        }
        atualizarContadores();
      }

      // Botão Adicionar ao Carrinho
      if (e.target.classList.contains('btn-add')) {
        const id = parseInt(e.target.dataset.id);
        const nome = e.target.dataset.nome;
        const preco = parseFloat(e.target.dataset.preco);

        const itemExistente = carrinho.find(item => item.id === id);
        if (itemExistente) {
          itemExistente.qtd += 1;
        } else {
          carrinho.push({ id, nome, preco, qtd: 1 });
        }
        atualizarContadores();
      }
    });
  }

  // Abertura e Fechamento do Modal de Cadastro
  if (btnAccount && modalCliente) {
    btnAccount.addEventListener('click', () => modalCliente.classList.add('active'));
  }

  if (btnCloseModal && modalCliente) {
    btnCloseModal.addEventListener('click', () => modalCliente.classList.remove('active'));
  }

  // Envio do Formulário de Cadastro via API
  if (formCadastro) {
    formCadastro.addEventListener('submit', async (e) => {
      e.preventDefault();

      const dados = {
        nome: document.getElementById('cad-nome').value.trim(),
        email: document.getElementById('cad-email').value.trim(),
        senha: document.getElementById('cad-senha').value
      };

      try {
        const response = await fetch('index.php?controller=cliente&action=cadastrarJson', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(dados)
        });

        const res = await response.json();

        if (res.sucesso) {
          alert('Cadastro realizado com sucesso!');
          formCadastro.reset();
          if (modalCliente) modalCliente.classList.remove('active');
        } else {
          alert(res.erro || 'Erro ao realizar cadastro.');
        }
      } catch (err) {
        console.error(err);
        alert('Erro de conexão ao tentar cadastrar.');
      }
    });
  }

  // Inicialização da Loja
  carregarProdutos();
  atualizarContadores();
});