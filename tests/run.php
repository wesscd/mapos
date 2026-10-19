<?php

// A dedicated, disposable database is mandatory; the application database is never reset.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
chdir(dirname(__DIR__));
require 'application/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(getcwd() . '/application')->load();
if (($_ENV['DB_DATABASE'] ?? '') === 'mapos_fork_test') {
    fwrite(STDERR, "The application cannot use the disposable test database.\n");
    exit(1);
}
$database = new mysqli($_ENV['DB_HOSTNAME'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], 'mapos_fork_test');
$database->query('SET FOREIGN_KEY_CHECKS=0');
foreach ($database->query('SHOW TABLES')->fetch_all() as $table) {
    // Names originate from MySQL metadata; double backticks escape identifiers.
    $database->query('DROP TABLE `' . str_replace('`', '``', $table[0]) . '`');
}
$database->query('SET FOREIGN_KEY_CHECKS=1');
$sql = str_replace(['admin_name', 'admin_email', 'admin_password', 'admin_created_at'], ['Test Admin', 'admin@example.test', password_hash('test-password', PASSWORD_DEFAULT), date('Y-m-d H:i:s')], file_get_contents('banco.sql'));
$database->multi_query($sql);
do {
    if ($result = $database->store_result()) {
        $result->free();
    }
} while ($database->more_results() && $database->next_result());
$database->close();
$_ENV['DB_DATABASE'] = 'mapos_fork_test';
$_ENV['APP_ENVIRONMENT'] = 'testing';
$_SERVER['argv'] = ['index.php', 'quality', 'run'];
$_SERVER['argc'] = 3;
require 'index.php';
