CREATE TABLE payments (
    id_payment VARCHAR(50) PRIMARY KEY,
    event_id VARCHAR(50) NOT NULL,
    customer_id VARCHAR(50) NOT NULL,
    payment_intent_id VARCHAR(50) NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    payload JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

stripe listen --forward-to localhost:8000/public/webhook.php --log-level debug

docker exec -it stripe_mysql bash

mysql -u test_user -p
