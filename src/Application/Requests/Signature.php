<?php
namespace Application\Requests;

use InvalidArgumentException;

/**
 * Signature
 * @package Application\Requests
 * @author Maxim Alexeev
 * @version 1.0.1
 */
final class Signature
{
    /**
     * Параметры, необходимые для генерации подписи.
     */
    public const REQUIRED_PARAMETERS = ['uid', 'suid', 'aid', 'authKey', 'sessionKey', 'clientPlatform'];

    /**
     * Сгенерировать подпись для запроса.
     * @param string $controller Контроллер.
     * @param string $action Действие.
     * @param array $parameters Параметры.
     * @param array|null $requestParameters Параметры запроса.
     * @return string
     */
    public static function generate(
        string $controller,
        string $action,
        array $parameters,
        ?array $requestParameters = null
    ): string
    {
        $request = $requestParameters ?? [];
        $request['controller'] = mb_strtolower($controller);
        $request['action'] = mb_strtolower($action);

        foreach (self::REQUIRED_PARAMETERS as $key) {
            if (isset($parameters[$key]) === false) {
                throw new InvalidArgumentException("Не указан обязательный параметр '{$key}'!");
            }
            $request[$key] = $parameters[$key];
        }

        return self::calculate($request);
    }

    /**
     * Вычислить MD5 хэш массива.
     * @param array $array Массив.
     * @return string
     */
    public static function calculate(array $array): string
    {
        $parts = [];
        ksort($array, SORT_STRING);

        foreach ($array as $key => $value) {
            if (is_array($value) === true) {
                $value = self::calculate($value);
            }
            $parts[] .= "{$key}={$value}";
        }

        return md5(join('&', $parts));
    }
}
