<?php
require_once __DIR__ . '/../config/db.php';

class Cliente
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    public function cadastrar(string $nome, string $email, string $senha): int
    {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("
            INSERT INTO cliente (nome, email, senha)
            VALUES (:nome, :email, :senha)
        ");
        $stmt->execute([
            ':nome'  => $nome,
            ':email' => $email,
            ':senha' => $hash
        ]);

        return (int) $this->conn->lastInsertId();
    }
}