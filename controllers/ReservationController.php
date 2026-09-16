<?php

require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/RefundController.php';

class ReservationController
{
    private Reservation $reservationModel;
    private Notification $notificationModel;
    private User $userModel;

    public function __construct()
    {
        $this->reservationModel = new Reservation();
        $this->notificationModel = new Notification();
        $this->userModel = new User();
    }

    /**
     * Create a reservation.
     *
     * The customer selects a room type.
     * The system automatically assigns an available
     * physical room of that type.
     */
    public function create(
        int $userId,
        string $roomType,
        string $checkIn,
        string $checkOut,
        int $guests,
        string $specialRequests = ''
    ): array {

        if ($userId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid user.'
            ];
        }

        if ($roomType === '') {
            return [
                'success' => false,
                'message' => 'Please select a room type.'
            ];
        }

        if ($checkIn === '' || $checkOut === '') {
            return [
                'success' => false,
                'message' =>
                    'Please select your check-in and check-out dates.'
            ];
        }

        if ($checkOut <= $checkIn) {
            return [
                'success' => false,
                'message' =>
                    'Check-out must be after check-in.'
            ];
        }

        if ($guests < 1 || $guests > 10) {
            return [
                'success' => false,
                'message' =>
                    'Please enter a valid number of guests.'
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Find Available Physical Room
        |--------------------------------------------------------------------------
        */

        $room = $this->reservationModel->findAvailableRoom(
            $roomType,
            $checkIn,
            $checkOut,
            $guests
        );

        if (!$room) {
            return [
                'success' => false,
                'message' =>
                    'No rooms of this type are available for the selected dates.'
            ];
        }

        $roomId = (int) $room['id'];

        /*
        |--------------------------------------------------------------------------
        | Create Reservation
        |--------------------------------------------------------------------------
        */

        try {

            $reservationId =
                $this->reservationModel->create(
                    $userId,
                    $roomId,
                    $checkIn,
                    $checkOut,
                    $guests,
                    $specialRequests
                );

        } catch (PDOException $e) {

            return [
                'success' => false,
                'message' =>
                    'Database error while creating reservation.'
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Get Guest Information
        |--------------------------------------------------------------------------
        */

        $guest = $this->userModel->findById(
            $userId
        );

        $guestName = $guest['name'] ?? 'A guest';

        /*
        |--------------------------------------------------------------------------
        | Notify Administrators
        |--------------------------------------------------------------------------
        */

        try {

            $admins = $this->userModel->getAdmins();

            foreach ($admins as $admin) {

                $this->notificationModel->create(
                    (int) $admin['id'],
                    'New Reservation',
                    $guestName .
                    ' has submitted a new reservation request. ' .
                    'Reservation #' .
                    $reservationId .
                    ' requires your review.'
                );
            }

        } catch (PDOException $e) {

            // Reservation was already created.
            // Notification failure should not cancel it.
        }

        return [
            'success' => true,
            'id' => $reservationId,
            'room_number' => $room['room_number'],
            'message' =>
                'Reservation created successfully. Room ' .
                $room['room_number'] .
                ' has been assigned.'
        ];
    }


    /**
     * Cancel a reservation belonging to the logged-in user.
     */
    public function requestCancellation(
        int $reservationId,
        int $userId
    ): array {

        if ($reservationId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid reservation.'
            ];
        }

        if ($userId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid user.'
            ];
        }

        $success = $this->reservationModel->requestCancellation(
            $reservationId,
            $userId
        );

        if ($success) {
            $reservation = $this->reservationModel->getById($reservationId);
            $guestName = $reservation['customer_name'] ?? 'A customer';
            try {
                foreach ($this->userModel->getAdmins() as $admin) {
                    $this->notificationModel->create(
                        (int) $admin['id'],
                        'Cancellation Request',
                        $guestName . ' requested cancellation for reservation #' .
                        $reservationId . '.'
                    );
                }
            } catch (PDOException $e) {
                // The cancellation request was already saved.
            }
        }

        return [
            'success' => $success,
            'message' => $success
                ? 'Cancellation Request Submitted. Waiting for Admin Approval.'
                : 'Unable to submit a cancellation request for this reservation.'
        ];
    }

    public function cancel(int $reservationId, int $userId): array
    {
        return $this->requestCancellation($reservationId, $userId);
    }

    public function approveCancellation(int $reservationId): array
    {
        if ($reservationId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid reservation.'
            ];
        }

        $reservation = $this->reservationModel->getById($reservationId);
        if (!$reservation || $reservation['status'] !== 'cancel_requested') {
            return [
                'success' => false,
                'message' => 'This reservation is not awaiting cancellation approval.'
            ];
        }

        try {
            $refundResult = (new RefundController())
                ->refundReservation($reservationId);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Unable to process the refund. The reservation was not cancelled.'
            ];
        }

        if (!$refundResult['success']) {
            return [
                'success' => false,
                'message' => $refundResult['message']
                    ?? 'Unable to process the refund. The reservation was not cancelled.'
            ];
        }

        if (!$this->reservationModel->updateStatus($reservationId, 'cancelled')) {
            return [
                'success' => false,
                'message' => 'The refund was processed, but the reservation could not be cancelled.'
            ];
        }

        $message = 'Reservation #' . $reservationId . ' was cancelled successfully.';
        if (empty($refundResult['no_refund_required'])) {
            $message .= sprintf(
                ' Your original payment was $%0.2f. A 10%% service charge of $%0.2f was deducted and $%0.2f was refunded through Stripe.',
                $refundResult['original_amount'],
                $refundResult['service_charge'],
                $refundResult['refund_amount']
            );
        } else {
            $message .= ' No refund was required because this reservation had no successful payment.';
        }

        try {
            $this->notificationModel->create(
                (int) $reservation['user_id'],
                'Reservation Cancelled',
                $message
            );
        } catch (PDOException $e) {
            // The reservation and refund have already completed.
        }

        return [
            'success' => true,
            'message' => $message
        ];
    }


    /**
     * Get reservations belonging to a user.
     */
    public function userReservations(
        int $userId
    ): array {

        if ($userId <= 0) {
            return [];
        }

        return $this->reservationModel
            ->getUserReservations($userId);
    }


    /**
     * Get all reservations for admin.
     */
    public function allReservations(): array
    {
        return $this->reservationModel
            ->getAllReservations();
    }


    /**
     * Update reservation status by administrator.
     */
    public function updateStatus(
        int $reservationId,
        string $status
    ): array {

        if ($reservationId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid reservation.'
            ];
        }

        $allowedStatuses = [
            'pending',
            'confirmed',
            'cancel_requested',
            'cancelled'
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            return [
                'success' => false,
                'message' => 'Invalid reservation status.'
            ];
        }

        if ($status === 'cancelled') {
            $reservation = $this->reservationModel->getById($reservationId);
            $payment = (new Payment())->getSuccessfulPaidPayment($reservationId);
            if (!$reservation || $reservation['status'] !== 'pending'
                || ($payment && strtolower(trim((string) $payment['status'])) === 'paid')) {
                return [
                    'success' => false,
                    'message' => 'Paid or non-pending reservations must use the cancellation approval workflow.'
                ];
            }
        }

        try {

            $success = $this->reservationModel->updateStatus(
                $reservationId,
                $status
            );

            if (!$success) {
                return [
                    'success' => false,
                    'message' =>
                        'Unable to update reservation status.'
                ];
            }

            if ($status === 'confirmed') {
                $reservation = $this->reservationModel->getById($reservationId);
                if ($reservation) {
                    try {
                        $this->notificationModel->create(
                            (int) $reservation['user_id'],
                            'Reservation Approved',
                            'Your reservation #' . $reservationId .
                            ' has been approved. You can now proceed with payment.'
                        );
                    } catch (PDOException $e) {
                        // Approval already completed; notification failure is non-blocking.
                    }
                }
                return [
                    'success' => true,
                    'message' =>
                        'Reservation approved successfully.'
                ];
            }

            if ($status === 'cancelled') {
                return [
                    'success' => true,
                    'message' => 'Reservation cancelled successfully.'
                ];
            }

            return [
                'success' => true,
                'message' =>
                    'Reservation status updated successfully.'
            ];

        } catch (PDOException $e) {

            return [
                'success' => false,
                'message' =>
                    'Database error while updating reservation.'
            ];
        }
    }
}

?>