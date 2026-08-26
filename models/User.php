<?php

require_once __DIR__ . '/../config/database.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([
            $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Find a user by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $id
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Get all administrators.
     *
     * Used by the notification system to notify
     * every administrator when a guest makes
     * a new reservation.
     */
    public function getAdmins(): array
    {
        $stmt = $this->db->query("
            SELECT
                id,
                name,
                email,
                role
            FROM users
            WHERE role = 'admin'
            ORDER BY id ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new guest account.
     */
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

        $stmt = $this->db->prepare("
            INSERT INTO users
            (
                name,
                email,
                phone,
                address,
                password,
                role
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                'guest'
            )
        ");

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
?>