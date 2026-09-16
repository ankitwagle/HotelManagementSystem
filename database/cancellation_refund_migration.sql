ALTER TABLE payments
    ADD COLUMN service_charge DECIMAL(10,2) NULL,
    ADD COLUMN refund_amount DECIMAL(10,2) NULL,
    ADD COLUMN refund_reference VARCHAR(100) NULL,
    ADD COLUMN refunded_at TIMESTAMP NULL;
