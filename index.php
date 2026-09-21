<?php
session_start();
require_once __DIR__ . '/config/db.php';
Database::getConnection();

// Roteamento simples via GET
$controller = $_GET['controller'] ?? 'auth';
$action = $_GET['action'] ?? 'form';

// Carregar controller
switch ($controller) {
    case 'auth':
        require_once __DIR__ . '/controllers/AuthController.php';
        $c = new AuthController();
        break;

    // LOJA VITRINE CLIENT-SIDE (NOVO)
    case 'loja':
        require_once __DIR__ . '/controllers/LojaController.php';
        $c = new LojaController();
        break;

    // CLIENTES & CADASTROS (NOVO)
    case 'cliente':
        require_once __DIR__ . '/controllers/ClienteController.php';
        $c = new ClienteController();
        break;

    // CRUD PRODUTOS E VENDAS
    case 'produto':
        require_once __DIR__ . '/controllers/ProdutoController.php';
        $c = new ProdutoController();
        break;

    case 'entrada':
        require_once __DIR__ . '/controllers/EntradaController.php';
        $c = new EntradaController();
        break;

    case 'venda':
        require_once __DIR__ . '/controllers/VendaController.php';
        $c = new VendaController();
        break;

    case 'relatorio':
        require_once __DIR__ . '/controllers/RelatorioController.php';
        $c = new RelatorioController();
        break;
    
    case 'fornecedor':
        require_once __DIR__ . '/controllers/FornecedorController.php';
        $c = new FornecedorController();
        break;

    case 'cep':
        require_once __DIR__ . '/controllers/CepController.php';
        $c = new CepController();
        break;

    case 'categoria':
        require_once __DIR__ . '/controllers/CategoriaController.php';
        $c = new CategoriaController();
        break;

    case 'variacao':
        require_once __DIR__ . '/controllers/VariacaoController.php';
        $c = new VariacaoController();
        break;

    // CRUD USUÁRIO / VENDEDOR
    case 'usuario':
        require_once __DIR__ . '/controllers/UsuarioController.php';
        $c = new UsuarioController();
        break;

    // Caminho padrão caso o controller não exista
    default:
        die("Controller inválido.");
}

// Executar ação
if (!method_exists($c, $action)) {
    die("Ação inválida.");
}

$c->$action();