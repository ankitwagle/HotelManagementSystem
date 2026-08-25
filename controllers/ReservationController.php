
<?php

require_once __DIR__ . '/../models/Reservation.php';

class ReservationController
{
    private Reservation $reservationModel;

    public function __construct()
    {
        $this->reservationModel = new Reservation();
    }

    public function create(
        int $userId,
        int $roomId,
        string $checkIn,
        string $checkOut,
        int $guests,
        string $specialRequests = ''
    ): array {

        if ($roomId <= 0) {
            return [
                'success' => false,
                'message' => 'Please select a valid room.'
            ];
        }

        if ($checkIn === '' || $checkOut === '') {
            return [
                'success' => false,
                'message' => 'Please select your check-in and check-out dates.'
            ];
        }

        if ($checkOut <= $checkIn) {
            return [
                'success' => false,
                'message' => 'Check-out must be after check-in.'
            ];
        }

        if ($guests < 1 || $guests > 10) {
            return [
                'success' => false,
                'message' => 'Please enter a valid number of guests.'
            ];
        }

        /*
         * Check whether the room is already reserved
         * for any overlapping dates.
         */
        if (!$this->reservationModel->isAvailable(
            $roomId,
            $checkIn,
            $checkOut
        )) {
            return [
                'success' => false,
                'message' => 'This room is already reserved for the selected dates.'
            ];
        }

        $reservationId = $this->reservationModel->create(
            $userId,
            $roomId,
            $checkIn,
            $checkOut,
            $guests,
            $specialRequests
        );

        return [
            'success' => true,
            'id' => $reservationId,
            'message' => 'Reservation created successfully.'
        ];
    }

    /**
     * Cancel a pending reservation belonging to the logged-in user.
     */
    public function cancel(
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

        $success = $this->reservationModel->cancel(
            $reservationId,
            $userId
        );

        return [
            'success' => $success,
            'message' => $success
                ? 'Reservation cancelled successfully.'
                : 'Unable to cancel this reservation.'
        ];
    }

    /**
     * Get all reservations belonging to a user.
     */
    public function userReservations(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        return $this->reservationModel->getUserReservations(
            $userId
        );
    }

    /**
     * Get all reservations for admin.
     */
    public function allReservations(): array
    {
        return $this->reservationModel->getAllReservations();
    }

    /**
     * Update reservation status.
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
            'approved',
            'rejected',
            'cancelled'
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            return [
                'success' => false,
                'message' => 'Invalid reservation status.'
            ];
        }

        $success = $this->reservationModel->updateStatus(
            $reservationId,
            $status
        );

        return [
            'success' => $success,
            'message' => $success
                ? 'Reservation status updated successfully.'
                : 'Unable to update reservation status.'
        ];
    }
}
