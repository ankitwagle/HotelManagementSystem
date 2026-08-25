
<?php

require_once __DIR__ . '/../config/database.php';

class Reservation
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Check whether a room is available for the selected dates.
     */
    public function isAvailable(
        int $roomId,
        string $checkIn,
        string $checkOut
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM reservations
            WHERE room_id = ?
              AND status IN ('pending', 'approved')
              AND check_in < ?
              AND check_out > ?
        ");

        $stmt->execute([
            $roomId,
            $checkOut,
            $checkIn
        ]);

        return (int) $stmt->fetchColumn() === 0;
    }

    /**
     * Create a new reservation.
     */
    public function create(
        int $userId,
        int $roomId,
        string $checkIn,
        string $checkOut,
        int $guests,
        string $specialRequests = ''
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO reservations
            (
                user_id,
                room_id,
                check_in,
                check_out,
                guests,
                special_requests,
                status,
                created_at
            )
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");

        $stmt->execute([
            $userId,
            $roomId,
            $checkIn,
            $checkOut,
            $guests,
            $specialRequests
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get all reservations belonging to one user.
     */
    public function getUserReservations(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                r.*,
                rm.room_type AS room_name,
                rm.price
            FROM reservations r
            INNER JOIN rooms rm
                ON r.room_id = rm.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
        ");

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cancel a pending reservation belonging to the logged-in user.
     */
    public function cancel(
        int $reservationId,
        int $userId
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE reservations
            SET status = 'cancelled'
            WHERE id = ?
              AND user_id = ?
              AND status = 'pending'
        ");

        $stmt->execute([
            $reservationId,
            $userId
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Get all reservations for the admin.
     */
    public function getAllReservations(): array
    {
        $stmt = $this->db->query("
            SELECT
                r.*,
                u.name AS customer_name,
                u.email AS customer_email,
                rm.room_type AS room_name,
                rm.price
            FROM reservations r
            INNER JOIN users u
                ON r.user_id = u.id
            INNER JOIN rooms rm
                ON r.room_id = rm.id
            ORDER BY r.created_at DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update reservation status.
     */
    public function updateStatus(
    int $reservationId,
    string $status
): bool {
    $allowedStatuses = [
        'pending',
        'confirmed',
        'cancelled'
    ];

    if (!in_array($status, $allowedStatuses, true)) {
        return false;
    }

    $stmt = $this->db->prepare("
        UPDATE reservations
        SET status = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $status,
        $reservationId
    ]);

    return $stmt->rowCount() > 0;
}
}