<?php

require_once __DIR__ . '/../config/database.php';

class Otp
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function createOtp(
        int $userId,
        int $reservationId,
        string $otpCode
    ): bool {

        // Remove previous unused OTPs for this reservation
        $deleteStmt = $this->db->prepare("
            DELETE FROM otps
            WHERE user_id = ?
            AND reservation_id = ?
            AND is_used = 0
        ");

        $deleteStmt->execute([
            $userId,
            $reservationId
        ]);


        // Create new OTP valid for 10 minutes
        $stmt = $this->db->prepare("
            INSERT INTO otps
            (
                user_id,
                reservation_id,
                otp_code,
                expires_at,
                is_used,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                DATE_ADD(NOW(), INTERVAL 30 SECOND),
                0,
                NOW()
            )
        ");

        return $stmt->execute([
            $userId,
            $reservationId,
            $otpCode
        ]);
    }


    public function verifyOtp(
        int $userId,
        int $reservationId,
        string $otpCode
    ): bool {

        $stmt = $this->db->prepare("
            SELECT id
            FROM otps
            WHERE user_id = ?
            AND reservation_id = ?
            AND otp_code = ?
            AND is_used = 0
            AND expires_at > NOW()
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute([
            $userId,
            $reservationId,
            $otpCode
        ]);

        $otp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otp) {
            return false;
        }


        // Mark OTP as used
        $updateStmt = $this->db->prepare("
            UPDATE otps
            SET is_used = 1
            WHERE id = ?
        ");

        return $updateStmt->execute([
            $otp['id']
        ]);
    }
}

?>