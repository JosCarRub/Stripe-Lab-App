// test-webhook.php en la carpeta public
<?php
file_put_contents('test.log', 'Endpoint accedido: ' . date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
echo json_encode(['status' => 'ok']);