<?php
/*

return function(PDO $pdo) {
    $sql = <<<SQL
                CREATE TABLE IF NOT EXISTS payments (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        event_id VARCHAR(255) NOT NULL,
                        customer_id VARCHAR(255) NULL,
                        payment_intent_id VARCHAR(255) NULL,
                        status VARCHAR(100) NOT NULL,
                        payload JSON NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
SQL;
    $pdo->exec($sql);
};*/

/*
 * CREATE TABLE invoices (
    id_intern_invoice VARCHAR(50) PRIMARY KEY,
    invoice_id VARCHAR(255) NOT NULL,
    payment_id VARCHAR(50),
    customer_id VARCHAR(50) NOT NULL,
    invoice_number VARCHAR(50),
    amount INT NOT NULL,
    currency VARCHAR(3) NOT NULL,
    status VARCHAR(50) NOT NULL,
    invoice_pdf VARCHAR(255),
    hosted_invoice_url VARCHAR(255),
    customer_email VARCHAR(255),
    customer_name VARCHAR(255),
    subscription_id VARCHAR(50),
    period_start BIGINT,
    period_end BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id_payment)
);
 *
 *
 *
 */