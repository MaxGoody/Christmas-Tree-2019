<?php
namespace Application;

use InvalidArgumentException;

/**
 * Config
 * @package Application
 * @author Maxim Alexeev
 * @version 1.0.0
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
     * Загрузить конфигурацию из файла.
     * @param string $path
     * @return void
     */
    public static function loadFromFile(string $path): void
    {
        if (is_file($path) === false) {
            throw new InvalidArgumentException('Файла конфигурации по указанному пути не найдено!');
        }

        $config = json_decode(file_get_contents($path), true, JSON_THROW_ON_ERROR);

        self::parseArray($config);
    }

    /**
     * Разобрать массив.
     * @param array $array
     * @return void
     */
    private static function parseArray(array $array): void
    {
        if (empty($array['id']) === true || (Int)$array['id'] != $array['id']) {
            throw new InvalidArgumentException('Параметр "id" должен быть числом!');
        }
        self::set('id', $array['id']);

        if (empty($array['authKey']) === true ||
            is_string($array['authKey']) === false ||
            mb_strlen($array['authKey']) !== 32) {
            throw new InvalidArgumentException('Параметр "authKey" должен быть MD5 хэшем!');
        }
        self::set('authKey', $array['authKey']);

        self::set('mail', [
            'in' => isset($array['mail']['in']) === true && is_bool($array['mail']['in']) ?
                $array['mail']['in'] :
                false,
            'out' => isset($array['mail']['out']) === true && is_bool($array['mail']['out']) ?
                $array['mail']['out'] :
                false
        ]);
        self::set('elf', [
            'collect' => isset($array['elf']['collect']) === true && is_bool($array['elf']['collect']) ?
                $array['elf']['collect'] :
                false,
            'start' => isset($array['elf']['start']) === true && is_bool($array['elf']['start']) ?
                $array['elf']['start'] :
                false
        ]);

        self::set(
            'friends',
            isset($array['friends']) === true && is_array($array['friends']) ?
                $array['friends'] :
                []
        );
        self::set(
            'generators',
            isset($array['generators']) === true && is_bool($array['generators']) ?
                $array['generators'] :
                false
        );
        self::set(
            'quests',
            isset($array['quests']) === true && is_bool($array['quests']) ?
                $array['quests'] :
                false
        );
        self::set(
            'chests',
            isset($array['chests']) === true && is_bool($array['chests']) ?
                $array['chests'] :
                false
        );
        self::set(
            'wands',
            isset($array['wands']) === true && is_bool($array['wands']) ?
                $array['wands'] :
                false
        );
    }

    /**
     * Получить элемент конфигурации.
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $path, $default = '')
    {
        $symbolic = self::getSymbolic($path);
        return $symbolic ?? $default;
    }

    /**
     * Задаёт элементу конфигурации значение.
     * @param string $path
     * @param mixed $value
     * @return void
     */
    public static function set(string $path, $value): void
    {
        $symbolic =& self::getSymbolic($path);
        $symbolic = $value;
    }

    /**
     * Получить символьную ссылку на элемент конфигурации.
     * @param string $path
     * @return mixed
     */
    private static function &getSymbolic(string $path)
    {
        if (isset(self::$cached[$path]) === true) {
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
