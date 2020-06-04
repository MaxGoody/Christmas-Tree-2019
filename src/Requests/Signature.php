<?php

namespace Application\Requests;

use InvalidArgumentException;

/**
 * @package Application\Requests
 */
final class Signature
{
    public const REQUIRED_PARAMETERS = ['uid', 'suid', 'aid', 'authKey', 'sessionKey', 'clientPlatform'];

    /**
     * Generate signature to request.
     * @param string $controller
     * @param string $action
     * @param array $parameters
     * @param array|null $requestParameters
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
                throw new InvalidArgumentException("Required parameter '{$key}' is not specified!");
            }
            $request[$key] = $parameters[$key];
        }

        return self::calculate($request);
    }

    /**
     * Calculate MD5 hash of array.
     * @param array $array
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
