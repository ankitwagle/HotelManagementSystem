<?php

require_once __DIR__ . '/../config/database.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE email = ? LIMIT 1"
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE id = ? LIMIT 1"
        );

        $stmt->execute([$id]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function create(
        string $name,
        string $email,
        string $phone,
        string $address,
        string $password
    ): int {

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $this->db->prepare(
            "INSERT INTO users
            (name, email, phone, address, password, role)
            VALUES (?, ?, ?, ?, ?, 'customer')"
        );

        $stmt->execute([
            $name,
            $email,
            $phone,
            $address,
            $hashedPassword
        ]);

        return (int) $this->db->lastInsertId();
    }
}