<?php

namespace Application\Requests;

/**
 * TODO: This class may be used for getting encrypted XML file of build.
 * @package Application\Requests
 */
final class Decoder
{
    public const ENCRYPTION_KEY = '~cq@337{SRRESHk$?fcJ~@x%1kRpd2WS';

    /**
     * Decode packed string.
     * @param string $string
     * @return string
     */
    public static function decode(string $string)
    {
        $inflate = inflate_init(ZLIB_ENCODING_DEFLATE);

        $string = base64_decode($string);
        $string = self::xorString($string, self::ENCRYPTION_KEY);
        $string = base64_decode($string);

        return inflate_add($inflate, $string);
    }

    /**
     * Xor string.
     * @param string $string
     * @param string $key
     * @return string
     */
    private static function xorString(string $string, string $key): string
    {
        $stringLength = mb_strlen($string);
        $keyLength = mb_strlen($key);

        for ($index = 0; $index < $stringLength; ++$index) {
            $string[$index] = chr(ord($string[$index]) ^ ord($key[$index % $keyLength]));
        }

        return $string;
    }
}
