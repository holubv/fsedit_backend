<?php


namespace FSEdit;

use ForceUTF8\Encoding;

class Utils
{
    const KEY_SPACE = '0123456789abcdefghijklmnopqrstuvwxyz';

    /**
     * Generate a random string, using a cryptographically secure
     * pseudo-random number generator (random_int)
     *
     * @param int $length required string length
     *
     * @return string random string
     */
    public static function randomStr($length)
    {
        $pieces = [];
        try {
            for ($i = 0; $i < $length; ++$i) {
                $pieces[] = self::KEY_SPACE[random_int(0, 35)];
            }
        } catch (\Exception $e) {
            throw new \Error($e->getMessage());
        }
        return implode('', $pieces);
    }

    /**
     * @return string
     */
    public static function randomSha1()
    {
        return sha1(microtime(true) . mt_rand(10000, 90000));
    }

    /**
     * @param array $arr
     * @return mixed|null
     */
    public static function arrLast($arr)
    {
        if (!$arr) {
            return null;
        }
        return $arr[count($arr) - 1];
    }

    /**
     * @param string $path
     * @return bool
     * @throws \Exception
     */
    public static function convertFileToUTF8($path)
    {
        $string = file_get_contents($path);
        if ($string === false) {
            throw new \Exception('cannot read file');
        }
        if (mb_check_encoding($string, 'UTF-8')) {
            return false;
        }
        $string = Encoding::toUTF8($string);
        if (file_put_contents($path, $string) === false) {
            throw new \Exception('cannot write to file');
        }
        return true;
    }
}