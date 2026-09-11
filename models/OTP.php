<?php

require_once __DIR__ . '/../config/database.php';

class Otp
{
    private PDO $db;

    private const MAX_ATTEMPTS = 5;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function createOtp(
        int $userId,
        int $reservationId,
        string $otpCode
    ): bool {

        // Remove previous unused OTPs for this reservation.
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

        // Hash the OTP before storing it.
        $otpHash = password_hash(
            $otpCode,
            PASSWORD_DEFAULT
        );

        if ($otpHash === false) {
            return false;
        }

        // Create a new OTP valid for 30 seconds.
        $stmt = $this->db->prepare("
            INSERT INTO otps
            (
                user_id,
                reservation_id,
                otp_code,
                expires_at,
                is_used,
                attempts,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                DATE_ADD(NOW(), INTERVAL 30 SECOND),
                0,
                0,
                NOW()
            )
        ");

        return $stmt->execute([
            $userId,
            $reservationId,
            $otpHash
        ]);
    }


    public function verifyOtp(
        int $userId,
        int $reservationId,
        string $otpCode
    ): bool {

        $stmt = $this->db->prepare("
            SELECT
                id,
                otp_code,
                attempts
            FROM otps
            WHERE user_id = ?
              AND reservation_id = ?
              AND is_used = 0
              AND expires_at > NOW()
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute([
            $userId,
            $reservationId
        ]);

        $otp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otp) {
            return false;
        }

        /*
         * Stop verification after the maximum number of attempts.
         */
        if ((int) $otp['attempts'] >= self::MAX_ATTEMPTS) {

            $invalidateStmt = $this->db->prepare("
                UPDATE otps
                SET is_used = 1
                WHERE id = ?
            ");

            $invalidateStmt->execute([
                $otp['id']
            ]);

            return false;
        }

        /*
         * Verify the submitted OTP against the stored hash.
         */
        $valid = password_verify(
            $otpCode,
            $otp['otp_code']
        );

        if (!$valid) {

            /*
             * Increase failed-attempt counter.
             */
            $attemptStmt = $this->db->prepare("
                UPDATE otps
                SET
                    attempts = attempts + 1,
                    is_used = CASE
                        WHEN attempts + 1 >= ? THEN 1
                        ELSE is_used
                    END
                WHERE id = ?
                  AND is_used = 0
            ");

            $attemptStmt->execute([
                self::MAX_ATTEMPTS,
                $otp['id']
            ]);

            return false;
        }

        /*
         * Correct OTP:
         * immediately mark it as used so it cannot be reused.
         */
        $updateStmt = $this->db->prepare("
            UPDATE otps
            SET is_used = 1
            WHERE id = ?
              AND is_used = 0
        ");

        $updateStmt->execute([
            $otp['id']
        ]);

        return $updateStmt->rowCount() === 1;
    }
}

?>