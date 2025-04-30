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