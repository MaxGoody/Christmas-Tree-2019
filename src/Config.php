<?php

namespace Application;

use InvalidArgumentException;

/**
 * @package Application
 */
final class Config
{
    /**
     * @var array
     */
    private static $config = [];

    /**
     * @var array
     */
    private static $cached = [];

    /**
     * Load configuration from file.
     * @param string $path
     */
    public static function loadFromFile(string $path): void
    {
        if (is_file($path) === false) {
            throw new InvalidArgumentException('Specified file is not exists!');
        }

        $config = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        self::load($config);
    }

    /**
     * Validate and load configuration from array.
     * @param array $array
     */
    private static function load(array $array): void
    {
        if (empty($array['id']) || ctype_digit($array['id']) === false) {
            throw new InvalidArgumentException('Parameter "ID" must be a number!');
        }
        self::set('id', intval($array['id']));

        if (
            empty($array['authKey']) ||
            is_string($array['authKey']) === false ||
            preg_match('/^[a-f0-9]{32}$/', $array['authKey']) === false
        ) {
            throw new InvalidArgumentException('Parameter "authKey" must be a MD5 hash!');
        }
        self::set('authKey', $array['authKey']);

        self::set('mail', [
            'in' => isset($array['mail']['in']) && is_bool($array['mail']['in']) ? $array['mail']['in'] : false,
            'out' => isset($array['mail']['out']) && is_bool($array['mail']['out']) ? $array['mail']['out'] : false
        ]);
        self::set('elf', [
            'collect' => isset($array['elf']['collect']) && is_bool($array['elf']['collect']) ? $array['elf']['collect'] : false,
            'start' => isset($array['elf']['start']) && is_bool($array['elf']['start']) ? $array['elf']['start'] : false
        ]);

        self::set(
            'friends',
            isset($array['friends']) && is_array($array['friends']) ? $array['friends'] : []
        );
        self::set(
            'generators',
            isset($array['generators']) && is_bool($array['generators']) ? $array['generators'] : false
        );
        self::set(
            'quests',
            isset($array['quests']) && is_bool($array['quests']) ? $array['quests'] : false
        );
        self::set(
            'chests',
            isset($array['chests']) && is_bool($array['chests']) ? $array['chests'] : false
        );
        self::set(
            'wands',
            isset($array['wands']) && is_bool($array['wands']) ? $array['wands'] : false
        );
    }

    /**
     * Retrieve configuration option.
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $path, $default = null)
    {
        $symbolic = self::getSymbolic($path);
        return $symbolic ?? $default;
    }

    /**
     * Set configration option.
     * @param string $path
     * @param mixed $value
     */
    public static function set(string $path, $value): void
    {
        $symbolic =& self::getSymbolic($path);
        $symbolic = $value;
    }

    /**
     * Retrieve symbolic link on configuration option.
     * @param string $path
     * @return mixed
     */
    private static function &getSymbolic(string $path)
    {
        if (isset(self::$cached[$path])) {
            return self::$cached[$path];
        }

        $parts = explode('.', $path);
        $symbolic =& self::$config;

        foreach ($parts as $part) {
            if (isset($symbolic[$part]) === false) {
                $symbolic[$part] = null;
            }
            $symbolic =& $symbolic[$part];
        }

        self::$cached[$path] =& $symbolic;

        return $symbolic;
    }
}
