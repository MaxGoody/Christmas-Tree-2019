<?php
namespace Application\Requests;

use RuntimeException;
use InvalidArgumentException;

/**
 * Sender
 * @package Application\Requests
 */
final class Sender
{
    /**
     * URL Сервера.
     */
    public const SERVER_URL = 'https://elka2019-server-vk.ereality.org';

    /**
     * Стандартные заголовки.
     */
    public const DEFAULT_HEADERS = [
        'Accept: */*',
        'Accept-Language: ru,en;q=0.9',
        'Connection: keep-alive',
        'Origin: https://elka2019-client-vk.ereality.org',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36',
    ];

    /**
     * Отправить подписанный запрос.
     * @param string $controller
     * @param string $action
     * @param array $parameters
     * @param array|null $requestParameters
     * @return mixed
     */
    public function sendSigned(
        string $controller,
        string $action,
        array $parameters,
        ?array $requestParameters = null
    ) {
        $body = $parameters;
        $body['params'] = $requestParameters ?? [];
        $body['sign'] = Signature::generate($controller, $action, $parameters, $requestParameters);
        $body = json_encode($body);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::SERVER_URL."/{$controller}/{$action}",
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array_merge(self::DEFAULT_HEADERS, [
                'Content-Length: '.mb_strlen($body),
                'Content-Type: application/json'
            ]),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $body
        ]);

        $response = curl_exec($curl);
        if ($response === false) {
            throw new RuntimeException(curl_error($curl), curl_errno($curl));
        }

        curl_close($curl);

        $response = json_decode($response, true, JSON_THROW_ON_ERROR);
        if (isset($response['error']) === true) {
            throw new RuntimeException($response['error']['text'], $response['error']['code']);
        }

        return $response['data'] ?? $response;
    }

    /**
     * Отправить запрос без подписи.
     * @param string $controller
     * @param string $action
     * @param array|null $query
     * @return mixed
     */
    public static function sendUnsigned(string $controller, string $action, ?array $query = null)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::SERVER_URL."/{$controller}/{$action}"
                .($query === null ? '' : '?'.http_build_query($query)),
            CURLOPT_HTTPHEADER => self::DEFAULT_HEADERS,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true
        ]);

        $response = curl_exec($curl);
        if ($response === false) {
            throw new RuntimeException(curl_error($curl), curl_errno($curl));
        }

        curl_close($curl);

        $response = json_decode($response, true, JSON_THROW_ON_ERROR);
        if (isset($response['error']) === true) {
            throw new RuntimeException($response['error']['text'], $response['error']['code']);
        }

        return $response;
    }
}
