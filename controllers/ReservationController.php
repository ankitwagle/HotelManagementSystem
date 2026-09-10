<?php

require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/User.php';

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
            'cancelled'
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            return [
                'success' => false,
                'message' => 'Invalid reservation status.'
            ];
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
                return [
                    'success' => true,
                    'message' =>
                        'Reservation approved successfully.'
                ];
            }

            if ($status === 'cancelled') {
                return [
                    'success' => true,
                    'message' =>
                        'Reservation cancelled successfully.'
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