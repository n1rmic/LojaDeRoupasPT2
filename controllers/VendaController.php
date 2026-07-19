<?php
require_once __DIR__ . '/../models/Venda.php';

class VendaController
{
    public function index(): void
    {
        $this->check();


        $vendaModel = new Venda();
        $vendas = $vendaModel->listarRecentes();


        $conn = Database::getConnection();


        $clientes = $conn->query("SELECT id, nome FROM cliente ORDER BY nome")->fetchAll();


        $variacoes = $conn->query("
            SELECT v.id, v.sku, v.tamanho, v.cor, v.preco, p.nome AS produto_nome
            FROM variacao v
            INNER JOIN produto p ON p.id = v.produto_id
            WHERE p.ativo = 1
            ORDER BY p.nome
        ")->fetchAll();


        require_once __DIR__ . '/../views/vendas.php';
    }


    public function salvar(): void
    {
        $this->check();


        $clienteId   = (int) ($_POST['cliente_id'] ?? 0);
        $variacaoIds = $_POST['variacao_id'] ?? [];
        $quantidades = $_POST['quantidade'] ?? [];
        $precos      = $_POST['preco_unitario'] ?? [];


        if ($clienteId <= 0) {
            die("Selecione um cliente.");
        }


        $itens = [];
        foreach ($variacaoIds as $i => $variacaoId) {
            $variacaoId = (int) $variacaoId;
            $quantidade = (int) ($quantidades[$i] ?? 0);
            $preco      = (float) str_replace(',', '.', $precos[$i] ?? '0');


            if ($variacaoId > 0 && $quantidade > 0) {
                $itens[] = [
                    'variacao_id'    => $variacaoId,
                    'quantidade'     => $quantidade,
                    'preco_unitario' => $preco,
                ];
            }
        }


        if (empty($itens)) {
            die("Informe ao menos um item válido para a venda.");
        }


        $vendaModel = new Venda();
        $usuarioId  = (int) $_SESSION['usuario_id'];


        try {
            $vendaModel->registrar($usuarioId, $clienteId, $itens);
        } catch (RuntimeException $e) {
            die("Não foi possível concluir a venda: " . htmlspecialchars($e->getMessage()));
        } catch (Throwable $e) {
            die("Erro inesperado ao registrar a venda.");
        }


        header("Location: index.php?controller=venda&action=index");
        exit;
    }


    // NOVO MÉTODO PARA EMITIR NOTA FISCAL
    public function emitirNota(): void
    {
        $this->check();


        $vendaId = (int) ($_GET['venda_id'] ?? 0);
        if ($vendaId <= 0) die("Venda inválida.");


        require_once __DIR__ . '/../models/NotaFiscal.php';
        $notaModel = new NotaFiscal();


        try {
            $notaModel->emitirParaVenda($vendaId);
        } catch (Throwable $e) {
            die("Não foi possível emitir a nota: " . htmlspecialchars($e->getMessage()));
        }


        header("Location: index.php?controller=venda&action=index");
        exit;
    }
    

    public function verNota(): void
    {
        $this->check();

        $vendaId = (int) ($_GET['venda_id'] ?? 0);
        if ($vendaId <= 0) die("Venda inválida.");

        require_once __DIR__ . '/../models/NotaFiscal.php';
        $notaModel = new NotaFiscal();
        $nota = $notaModel->buscarPorVenda($vendaId);

        if (!$nota) {
            die('Esta venda ainda não possui nota fiscal emitida. Volte e clique em "Emitir NF" primeiro.');
        }

        $vendaModel = new Venda();
        $venda = $vendaModel->buscarPorId($vendaId);

        if (!$venda) {
            die("Venda não encontrada.");
        }

        require_once __DIR__ . '/../views/nota_fiscal_venda.php';
    }

    private function check(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: index.php?controller=auth&action=form");
            exit;
        }
    }
}
