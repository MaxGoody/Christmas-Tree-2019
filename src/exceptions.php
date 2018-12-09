<?php
# Обработчик не пойманных исключений.
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
    Не обработано исключение "{$data['class']}":
        - Сообщение: "{$data['message']}";
        - Код: {$data['code']};
        - Файл: "{$data['file']}":{$data['line']};
        - Трейс: {$data['trace']}.
    EOF;
});
