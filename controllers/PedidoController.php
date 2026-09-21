<?php
require_once __DIR__ . '/SiteBaseController.php';
require_once __DIR__ . '/../models/ClienteSite.php';
require_once __DIR__ . '/../models/Pedido.php';

class PedidoController extends SiteBaseController
{
    /** index.php?controller=pedido&action=checkout  (exige login) */
    public function checkout(): void
    {
        $this->exigirLogin();

        $carrinho = new Carrinho();
        foreach ($carrinho->sincronizar() as $aviso) {
            flash('aviso', $aviso);
        }
        if ($carrinho->vazio()) {
            flash('aviso', 'Seu carrinho está vazio.');
            redirecionar(url('carrinho', 'index'));
        }

        $cliente = (new ClienteSite())->buscarPorCpf($this->clienteCpf());
        $endereco = $_SESSION['checkout_endereco'] ?? $cliente['endereco'];

        $this->render('checkout', [
            'carrinho' => $carrinho,
            'cliente'  => $cliente,
            'endereco' => $endereco,
            'erro'     => '',
        ], 'Finalizar compra');
    }

    /** POST: endereco */
    public function confirmar(): void
    {
        $this->exigirLogin();
        $this->exigirPost(url('pedido', 'checkout'));

        $endereco = trim((string) ($_POST['endereco'] ?? ''));
        $carrinho = new Carrinho();

        $avisos = $carrinho->sincronizar();
        if ($avisos) { // algo mudou (preço/estoque): mostra o resumo atualizado antes de fechar
            foreach ($avisos as $a) {
                flash('aviso', $a);
            }
            $_SESSION['checkout_endereco'] = $endereco;
            redirecionar(url('pedido', 'checkout'));
        }
        if ($carrinho->vazio()) {
            redirecionar(url('carrinho', 'index'));
        }
        if (mb_strlen($endereco) < 10 || mb_strlen($endereco) > 255) {
            $cliente = (new ClienteSite())->buscarPorCpf($this->clienteCpf());
            $this->render('checkout', [
                'carrinho' => $carrinho,
                'cliente'  => $cliente,
                'endereco' => $endereco,
                'erro'     => 'Informe o endereço de entrega completo (10 a 255 caracteres).',
            ], 'Finalizar compra');
            return;
        }

        try {
            $pedidoId = (new Pedido())->criar($this->clienteCpf(), $endereco, $carrinho->itens());
        } catch (RegraNegocioException $ex) {
            flash('erro', $ex->getMessage());
            redirecionar(url('carrinho', 'index'));
        } catch (Throwable $ex) {
            error_log('[loja] falha ao criar pedido: ' . $ex->getMessage());
            flash('erro', 'Não foi possível concluir o pedido agora. Seu carrinho foi mantido; tente novamente.');
            redirecionar(url('carrinho', 'index'));
        }

        $carrinho->limpar();
        unset($_SESSION['checkout_endereco']);
        redirecionar(url('pedido', 'confirmacao', ['id' => $pedidoId]));
    }

    /** index.php?controller=pedido&action=confirmacao&id=N */
    public function confirmacao(): void
    {
        $this->exigirLogin();
        $pedido = (new Pedido())->buscarDoCliente((int) ($_GET['id'] ?? 0), $this->clienteCpf());
        if (!$pedido) {
            http_response_code(404);
            $this->render('nao_encontrado', ['mensagem' => 'Pedido não encontrado.'], 'Pedido não encontrado');
            return;
        }
        $this->render('confirmacao', ['pedido' => $pedido], 'Pedido realizado');
    }

    /** index.php?controller=pedido&action=meusPedidos */
    public function meusPedidos(): void
    {
        $this->exigirLogin();
        $pedidos = (new Pedido())->listarDoCliente($this->clienteCpf());
        $this->render('meus_pedidos', ['pedidos' => $pedidos], 'Meus pedidos');
    }
}
