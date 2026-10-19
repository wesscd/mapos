<?php

// One-time CLI wrapper around the upstream installer. Credentials arrive on stdin.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
if (file_exists($root . '/application/.env')) {
    fwrite(STDERR, "MapOS is already configured; installation cancelled.\n");
    exit(1);
}

try {
    $settings = json_decode(file_get_contents('php://stdin'), true, 512, JSON_THROW_ON_ERROR);
    foreach (['dbpassword', 'full_name', 'email', 'password', 'base_url'] as $required) {
        if (empty($settings[$required]) || ! is_string($settings[$required])) {
            throw new RuntimeException('Missing installation field: ' . $required);
        }
    }
    // These values are inserted into the upstream SQL template.
    if (! preg_match('/^[\p{L}\p{N} ._-]+$/u', $settings['full_name'])
        || ! filter_var($settings['email'], FILTER_VALIDATE_EMAIL)
        || str_contains($settings['email'], "'")
        || ! filter_var($settings['base_url'], FILTER_VALIDATE_URL)) {
        throw new RuntimeException('Invalid administrator name, email or base URL.');
    }
    $settings['host'] = $settings['host'] ?? 'db';
    $settings['dbuser'] = $settings['dbuser'] ?? 'mapos';
    $settings['dbname'] = $settings['dbname'] ?? 'mapos';
    $database = new mysqli($settings['host'], $settings['dbuser'], $settings['dbpassword'], $settings['dbname']);
    if ($database->query('SHOW TABLES')->num_rows > 0) {
        throw new RuntimeException('Database is not empty; installation cancelled.');
    }
    $database->close();
    $_POST = array_merge($settings, ['enter_api_enabled' => 'false', 'enter_token_expire_time' => '3600']);
    chdir($root . '/install');
    require $root . '/install/do_install.php';
} catch (Throwable $error) {
    fwrite(STDERR, "Installation failed. Check configuration and database availability.\n");
    exit(1);
}
