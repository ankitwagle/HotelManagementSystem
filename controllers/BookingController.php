<?php

require_once __DIR__ . '/../models/Booking.php';

class BookingController
{
    private Booking $bookingModel;

    public function __construct()
    {
        $this->bookingModel = new Booking();
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
    ): array {

        try {

            $bookingId = $this->bookingModel->createFromReservation(
                $reservationId,
                $userId,
                $roomId,
                $checkIn,
                $checkOut,
                $guests,
                $specialRequests,
                $totalAmount
            );

            return [
                'success' => true,
                'id' => $bookingId,
                'message' => 'Booking created successfully.'
            ];

        } catch (PDOException $e) {

            return [
                'success' => false,
                'message' => 'Unable to create booking.'
            ];
        }
    }

    public function userBookings(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        return $this->bookingModel->getUserBookings($userId);
    }

    public function find(int $bookingId): ?array
    {
        if ($bookingId <= 0) {
            return null;
        }

        return $this->bookingModel->find($bookingId);
    }

    public function allBookings(): array
    {
        return $this->bookingModel->getAll();
    }
}
