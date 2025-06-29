CREATE TABLE `StripeTransactions` (
                                      `transaction_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                      `stripe_customer_id` VARCHAR(50) NULL,
                                      `customer_email` VARCHAR(255) NULL,
                                      `customer_name` VARCHAR(255) NULL,
                                      `transaction_type` ENUM('one_time_receipt', 'subscription_invoice') NOT NULL,
                                      `stripe_payment_intent_id` VARCHAR(50) UNIQUE NULL,
                                      `stripe_invoice_id` VARCHAR(50) UNIQUE NULL,
                                      `stripe_subscription_id` VARCHAR(50) NULL,
                                      `stripe_charge_id` VARCHAR(50) UNIQUE NULL,
                                      `amount` INT NOT NULL,
                                      `currency` VARCHAR(3) NOT NULL,
                                      `status` VARCHAR(50) NOT NULL,
                                      `description` VARCHAR(255) NULL,
                                      `document_url` VARCHAR(255) NULL,
                                      `pdf_url` VARCHAR(255) NULL,
                                      `period_start` TIMESTAMP NULL,
                                      `period_end` TIMESTAMP NULL,
                                      `transaction_date_stripe` TIMESTAMP NOT NULL,
                                      `created_at_local` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `StripeSubscriptions` (
                                       `subscription_id` VARCHAR(50) PRIMARY KEY,
                                       `stripe_customer_id` VARCHAR(50) NOT NULL,
                                       `customer_email` VARCHAR(255) NULL,
                                       `status` VARCHAR(50) NOT NULL,
                                       `stripe_price_id` VARCHAR(50) NOT NULL,
                                       `interval` VARCHAR(20) NULL,
                                       `current_period_start` TIMESTAMP NULL,
                                       `current_period_end` TIMESTAMP NULL,
                                       `cancel_at_period_end` BOOLEAN DEFAULT FALSE,
                                       `canceled_at` TIMESTAMP NULL,
                                       `ended_at` TIMESTAMP NULL,
                                       `latest_transaction_id` BIGINT UNSIGNED NULL,
                                       `created_at_stripe` TIMESTAMP NOT NULL,
                                       `created_at_local` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);