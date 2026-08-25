<?php

require_once __DIR__ . '/../config/database.php';

class Booking
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Create a booking from an approved reservation.
     */
    public function createFromReservation(
        int $reservationId,
        int $userId,
        int $roomId,
        string $checkIn,
        string $checkOut,
        int $guests,
        string $specialRequests,
        float $totalAmount
    ): int {

        // Prevent duplicate booking for the same reservation details.
        $check = $this->db->prepare("
            SELECT id
            FROM bookings
            WHERE user_id = ?
              AND room_id = ?
              AND check_in = ?
              AND check_out = ?
            LIMIT 1
        ");

        $check->execute([
            $userId,
            $roomId,
            $checkIn,
            $checkOut
        ]);

        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            return (int) $existing['id'];
        }

        $bookingReference =
            'LUXE-' .
            date('Ymd') .
            '-' .
            strtoupper(bin2hex(random_bytes(3)));

        $stmt = $this->db->prepare("
            INSERT INTO bookings
            (
                booking_reference,
                user_id,
                room_id,
                check_in,
                check_out,
                guests,
                special_requests,
                total_amount,
                status,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                'confirmed',
                NOW()
            )
        ");

        $stmt->execute([
            $bookingReference,
            $userId,
            $roomId,
            $checkIn,
            $checkOut,
            $guests,
            $specialRequests,
            $totalAmount
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get bookings belonging to a customer.
     */
    public function getUserBookings(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                b.*,
                r.room_type AS room_name,
                r.price
            FROM bookings b
            INNER JOIN rooms r
                ON b.room_id = r.id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
        ");

        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get one booking.
     */
    public function find(int $bookingId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                b.*,
                r.room_type AS room_name,
                r.price
            FROM bookings b
            INNER JOIN rooms r
                ON b.room_id = r.id
            WHERE b.id = ?
            LIMIT 1
        ");

        $stmt->execute([$bookingId]);

        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        return $booking ?: null;
    }

    /**
     * Get all bookings for admin.
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT
                b.*,
                u.name AS customer_name,
                u.email AS customer_email,
                r.room_type AS room_name
            FROM bookings b
            INNER JOIN users u
                ON b.user_id = u.id
            INNER JOIN rooms r
                ON b.room_id = r.id
            ORDER BY b.created_at DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
