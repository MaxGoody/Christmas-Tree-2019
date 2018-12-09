<?php
namespace Application\Requests;

/**
 * Decoder
 * @package Application\Requests
 * @author MaxGoody
 * @version 1.0.0
 */
final class Decoder
{
    /**
     * Ключ шифрования XOR.
     */
    public const ENCRYPTION_KEY = '~cq@337{SRRESHk$?fcJ~@x%1kRpd2WS';

    /**
     * Разкодировать запакованную строку.
     * @param string $string
     * @return string
     */
    public static function decode(string $string)
    {
        $inflate = inflate_init(ZLIB_ENCODING_DEFLATE);

        $string = base64_decode($string);
        $string = self::XORString($string, self::ENCRYPTION_KEY);
        $string = base64_decode($string);

        return inflate_add($inflate, $string);
    }

    /**
     * XOR (де)шифрование строки.
     * @param string $string Строка.
     * @param string $key Ключ.
     * @return string
     */
    private static function XORString(string $string, string $key): string
    {
        $stringLength = mb_strlen($string);
        $keyLength = mb_strlen($key);

        for ($index = 0; $index < $stringLength; ++$index) {
            $string[$index] = chr(ord($string[$index]) ^ ord($key[$index % $keyLength]));
        }

        return $string;
    }
}
