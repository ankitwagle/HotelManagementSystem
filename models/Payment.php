<?php

require_once __DIR__ . '/../config/database.php';

class Payment
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Get one reservation belonging to a user.
     *
     * Only confirmed reservations are eligible for payment.
     */
    public function getReservationForPayment(
        int $reservationId,
        int $userId
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                r.id AS reservation_id,
                r.user_id,
                r.room_id,
                r.check_in,
                r.check_out,
                r.guests,
                r.status AS reservation_status,
                r.created_at AS reservation_created_at,

                u.name AS customer_name,
                u.email AS customer_email,

                rm.room_type AS room_name,
                rm.price

            FROM reservations r

            INNER JOIN users u
                ON r.user_id = u.id

            INNER JOIN rooms rm
                ON r.room_id = rm.id

            WHERE r.id = ?
              AND r.user_id = ?
              AND r.status = 'confirmed'

            LIMIT 1
        ");

        $stmt->execute([
            $reservationId,
            $userId
        ]);

        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        return $reservation ?: null;
    }

    /**
     * Check whether a successful payment already exists
     * for a reservation.
     */
    public function getPaymentByReservation(
        int $reservationId
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                id,
                booking_id,
                amount,
                payment_method,
                transaction_reference,
                status,
                paid_at,
                created_at
            FROM payments
            WHERE booking_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute([
            $reservationId
        ]);

        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $payment ?: null;
    }

    /**
     * Create a payment record.
     */
    public function createPayment(
        int $reservationId,
        float $amount,
        string $paymentMethod,
        string $transactionReference
    ): int {

        $stmt = $this->db->prepare("
            INSERT INTO payments
            (
                booking_id,
                amount,
                payment_method,
                transaction_reference,
                status,
                paid_at,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                'paid',
                NOW(),
                NOW()
            )
        ");

        $stmt->execute([
            $reservationId,
            $amount,
            $paymentMethod,
            $transactionReference
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get all payments belonging to one user.
     */
    public function getUserPayments(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                p.id,
                p.booking_id,
                p.amount,
                p.payment_method,
                p.transaction_reference,
                p.status,
                p.paid_at,
                p.created_at,

                r.check_in,
                r.check_out,

                rm.room_type AS room_name

            FROM payments p

            INNER JOIN reservations r
                ON p.booking_id = r.id

            INNER JOIN rooms rm
                ON r.room_id = rm.id

            WHERE r.user_id = ?

            ORDER BY p.created_at DESC
        ");

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
