<?php

// Inserts one synthetic customer only in an explicitly selected loopback setup.
if (PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--local-fixture') {
    http_response_code(404);
    exit(1);
}
require dirname(__DIR__) . '/application/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__) . '/application')->load();
if (! in_array(parse_url($_ENV['APP_BASEURL'] ?? '', PHP_URL_HOST), ['localhost', '127.0.0.1'], true)) {
    fwrite(STDERR, "HTTP fixtures require a loopback application URL.\n");
    exit(1);
}
try {
    $db = new mysqli($_ENV['DB_HOSTNAME'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_DATABASE']);
    $db->set_charset('utf8mb4');
    $suffix = bin2hex(random_bytes(8));
    $name = 'Demonstracao HTTP - vistoria';
    $document = 'HTTP' . $suffix;
    $email = 'demo-' . $suffix . '@example.test';
    $password = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    $query = $db->prepare('INSERT INTO clientes (nomeCliente, documento, email, telefone, senha, dataCadastro) VALUES (?, ?, ?, ?, ?, CURRENT_DATE())');
    $phone = '000';
    $query->bind_param('sssss', $name, $document, $email, $phone, $password);
    $query->execute();
    echo json_encode(['customer' => $db->insert_id]);
} catch (Throwable $error) {
    fwrite(STDERR, "Unable to prepare local HTTP fixture.\n");
    exit(1);
}
