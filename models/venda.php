<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/Estoque.php';
 
class Venda
{
    private PDO $conn;
    private Estoque $estoqueModel;


    public function __construct()
    {
        $this->conn = Database::getConnection();
        $this->estoqueModel = new Estoque();
    }
 
    public function listarRecentes(int $limite = 20): array
    {
        $sql = "
            SELECT v.id, v.data, v.status, v.valor_total,
                   c.nome AS cliente_nome, u.nome AS vendedor_nome
            FROM venda v
            INNER JOIN cliente c ON c.id = v.cliente_id
            INNER JOIN usuario u ON u.id = v.usuario_id
            ORDER BY v.id DESC
            LIMIT :limite
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
 
    public function buscarPorId(int $id): ?array
{
    $stmt = $this->conn->prepare("SELECT * FROM venda WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $r = $stmt->fetch();
    return $r ?: null;
}
    /**
     * Registra uma venda completa, deduzindo o estoque de cada variação vendida.
     * Impede a operação caso qualquer item não tenha estoque suficiente.
     *
     * @param array $itens Cada item: ['variacao_id' => int, 'quantidade' => int, 'preco_unitario' => float]
     */
    public function registrar(int $usuarioId, int $clienteId, array $itens): int
    {
        if (empty($itens)) {
            throw new InvalidArgumentException("A venda precisa ter ao menos um item.");
        }
 
        $valorTotal = 0.0;
        foreach ($itens as $item) {
            $valorTotal += $item['quantidade'] * $item['preco_unitario'];
        }
 
        $this->conn->beginTransaction();
 
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO venda (usuario_id, cliente_id, data, status, valor_total)
                VALUES (:usuario_id, :cliente_id, CURDATE(), 'finalizada', :valor_total)
            ");
            $stmt->execute([
                ':usuario_id' => $usuarioId,
                ':cliente_id' => $clienteId,
                ':valor_total' => $valorTotal,
            ]);
            $vendaId = (int) $this->conn->lastInsertId();
 
            $stmtItem = $this->conn->prepare("
                INSERT INTO venda_item (venda_id, variacao_id, quantidade, preco_unitario)
                VALUES (:venda_id, :variacao_id, :quantidade, :preco_unitario)
            ");
 
            $stmtMov = $this->conn->prepare("
                INSERT INTO movimento_estoque (variacao_id, tipo, quantidade, origem, origem_id, data)
                VALUES (:variacao_id, 'saida', :quantidade, 'venda', :origem_id, NOW())
            ");
 
            foreach ($itens as $item) {
                // Deduz o estoque ANTES de gravar o item — se não houver estoque,
                // ajustar() lança exceção e a transação inteira é desfeita.
                $this->estoqueModel->ajustar((int) $item['variacao_id'], -(int) $item['quantidade']);
 
                $stmtItem->execute([
                    ':venda_id'       => $vendaId,
                    ':variacao_id'    => $item['variacao_id'],
                    ':quantidade'     => $item['quantidade'],
                    ':preco_unitario' => $item['preco_unitario'],
                ]);
 
                $stmtMov->execute([
                    ':variacao_id' => $item['variacao_id'],
                    ':quantidade'  => $item['quantidade'],
                    ':origem_id'   => $vendaId,
                ]);
            }
 
            $this->conn->commit();
            return $vendaId;
 
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
