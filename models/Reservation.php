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
     * Find an available physical room of the requested room type
     * for the selected dates.
     */
    public function findAvailableRoom(
        string $roomType,
        string $checkIn,
        string $checkOut
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                rm.id,
                rm.room_number,
                rm.room_type,
                rm.price,
                rm.capacity,
                rm.floor,
                rm.status
            FROM rooms rm
            WHERE rm.room_type = ?
              AND rm.status = 'available'
              AND NOT EXISTS (
                  SELECT 1
                  FROM reservations r
                  WHERE r.room_id = rm.id
                    AND r.status IN ('pending', 'confirmed')
                    AND r.check_in < ?
                    AND r.check_out > ?
              )
            ORDER BY rm.room_number ASC
            LIMIT 1
        ");

        $stmt->execute([
            $roomType,
            $checkOut,
            $checkIn
        ]);

        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        return $room ?: null;
    }


    /**
     * Check whether a specific physical room is available.
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
              AND status IN ('pending', 'confirmed')
              AND check_in < ?
              AND check_out > ?
        ");

        $stmt->execute([
            $roomId,
            $checkOut,
            $checkIn
        ]);

        return (int)$stmt->fetchColumn() === 0;
    }


    /**
     * Get a room by its ID.
     */
    public function getRoomById(int $roomId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                room_number,
                room_type,
                price,
                capacity,
                floor,
                status
            FROM rooms
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$roomId]);

        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        return $room ?: null;
    }


    /**
     * Create a reservation.
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

        return (int)$this->db->lastInsertId();
    }


    /**
     * Get reservations belonging to one customer.
     */
    public function getUserReservations(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                r.*,
                rm.room_type AS room_name,
                rm.room_number,
                rm.price
            FROM reservations r
            INNER JOIN rooms rm
                ON r.room_id = rm.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
        ");

        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Cancel a customer's pending reservation.
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
     * Get all reservations for admin.
     */
    public function getAllReservations(): array
    {
        $stmt = $this->db->query("
            SELECT
                r.id,
                r.user_id,
                r.room_id,

                u.name AS customer_name,
                u.email AS customer_email,

                rm.room_number,
                rm.room_type AS room_name,
                rm.price AS room_price,

                r.check_in,
                r.check_out,
                r.guests,
                r.special_requests,
                r.status,
                r.created_at

            FROM reservations r

            INNER JOIN users u
                ON r.user_id = u.id

            INNER JOIN rooms rm
                ON r.room_id = rm.id

            ORDER BY
                r.check_in ASC,
                rm.room_number ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Get all reservations for one physical room.
     */
    public function getRoomReservations(int $roomId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                r.id,
                r.user_id,
                r.room_id,

                u.name AS customer_name,
                u.email AS customer_email,

                rm.room_number,
                rm.room_type,

                r.check_in,
                r.check_out,
                r.guests,
                r.status,
                r.created_at

            FROM reservations r

            INNER JOIN users u
                ON r.user_id = u.id

            INNER JOIN rooms rm
                ON r.room_id = rm.id

            WHERE r.room_id = ?

            ORDER BY r.check_in ASC
        ");

        $stmt->execute([$roomId]);

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
?>