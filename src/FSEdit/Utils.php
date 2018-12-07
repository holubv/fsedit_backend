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
    public static function random_str($length)
    {
        $pieces = [];
        for ($i = 0; $i < $length; ++$i) {
            $pieces[] = self::KEY_SPACE[random_int(0, 35)];
        }
        return implode('', $pieces);
    }
}