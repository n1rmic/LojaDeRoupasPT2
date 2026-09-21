<?php
require_once __DIR__ . '/../config/db.php';

class ClienteController
{
    public function cadastrarJson(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $rawInput = file_get_contents('php://input');
        $dados = json_decode($rawInput, true);

        $nome  = trim($dados['nome'] ?? '');
        $email = trim($dados['email'] ?? '');


        if (empty($nome) || empty($email) || empty($senha)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Preencha todos os campos.']);
            exit;
        }

        try {
            require_once __DIR__ . '/../models/Cliente.php';
            $clienteModel = new Cliente();
            $clienteId = $clienteModel->cadastrar($nome, $email, $senha);

            echo json_encode(['sucesso' => true, 'cliente_id' => $clienteId]);
        } catch (Throwable $e) {
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao cadastrar: ' . $e->getMessage()]);
        }
        exit;
    }
}