<?php
# Автозагрузка классов.
spl_autoload_register(function (string $class) {
    $path = __DIR__.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
    if (is_file($path) === false) {
        throw new InvalidArgumentException("Не удалось найти класс '{$class}' в файле '{$path}'!");
    }

    /** @noinspection PhpIncludeInspection */
    require $path;
});
