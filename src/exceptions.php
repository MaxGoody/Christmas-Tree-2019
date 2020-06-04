<?php
set_exception_handler(function (Throwable $exception) {
    $data = [
        'class' => get_class($exception),
        'message' => $exception->getMessage(),
        'code' => $exception->getCode(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ];

    echo <<<EOF
    Exception "{$data['class']}":
        - Message: "{$data['message']}";
        - Code: {$data['code']};
        - File: "{$data['file']}":{$data['line']};
        - Trace: {$data['trace']}.
    EOF;
});
