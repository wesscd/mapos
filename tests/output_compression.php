<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('BASEPATH', __DIR__);
require dirname(__DIR__) . '/application/hooks/compress.php';

function &get_instance()
{
    global $instance;

    return $instance;
}

foreach ([
    ['image/jpeg', "\xff\xd8\t\r\n  binary\xff\xd9"],
    ['image/png', "\x89PNG\r\n\x1a\n  binary"],
    ['application/json', '{"message":"two  spaces"}'],
    ['text/html', '<p>two  spaces</p>'],
] as [$type, $bytes]) {
    $instance = (object) ['output' => new class($type, $bytes) {
        public $displayed;

        public function __construct(private string $type, private string $bytes)
        {
        }

        public function get_output()
        {
            return $this->bytes;
        }

        public function get_content_type()
        {
            return $this->type;
        }

        public function set_output($bytes)
        {
            $this->bytes = $bytes;
        }

        public function _display()
        {
            $this->displayed = $this->bytes;
        }
    }];
    compress();
    $expected = $type === 'text/html' ? '<p>two spaces</p>' : $bytes;
    if ($instance->output->displayed !== $expected) {
        throw new RuntimeException('Unexpected output transformation: ' . $type);
    }
}
echo "Output checks passed: JPEG, PNG and JSON preserved; HTML compacted.\n";
