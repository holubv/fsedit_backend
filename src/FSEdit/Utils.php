<?php


namespace FSEdit;


class Utils
{
    const KEY_SPACE = '0123456789abcdefghijklmnopqrstuvwxyz';

    /**
     * Generate a random string, using a cryptographically secure
     * pseudorandom number generator (random_int)
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

    public static function randomSha1()
    {
        return sha1(microtime(true) . mt_rand(10000, 90000));
    }
}