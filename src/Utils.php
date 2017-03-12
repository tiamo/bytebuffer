<?php

namespace Streams;

class Utils
{
    /**
     * A constant holding the minimum value a byte can
     * have, -2^7.
     */
    const BYTE_MIN_VALUE = -128;
    /**
     * A constant holding the maximum value a byte can
     * have, 2^7-1.
     */
    const BYTE_MAX_VALUE = 127;
    /**
     * A constant holding the minimum value a short can
     * have, -2^15.
     */
    const SHORT_MIN_VALUE = -32768;
    /**
     * A constant holding the maximum value a short can
     * have, 2^15-1.
     */
    const SHORT_MAX_VALUE = 32767;
    /**
     * A constant holding the minimum value an int can
     * have, -2^31.
     */
    const INTEGER_MIN_VALUE = -2147483648;
    /**
     * A constant holding the maximum value an int can
     * have, 2^31-1.
     */
    const INTEGER_MAX_VALUE = 2147483647;
    /**
     * A constant holding the minimum value a long can
     * have, -2^63.
     */
    const LONG_MIN_VALUE = -9223372036854775808;
    /**
     * A constant holding the maximum value a long can
     * have, 2^63-1.
     */
    const LONG_MAX_VALUE = 9223372036854775807;

    /**
     * Rounds X up to the next multiple of Y.
     * @param int $x
     * @param int $y
     * @return int
     */
    public static function roundUp($x, $y)
    {
        return ceil($x / $y) * $y;
    }

    /**
     * Rounds X down to the prev multiple of Y.
     * @param int $x
     * @param int $y
     * @return int
     */
    public static function roundDown($x, $y)
    {
        return floor($x / $y) * $y;
    }

    /**
     * Convert bytes to string
     * @param array $bytes
     * @return string
     */
    public static function bytesToString(array $bytes)
    {
        $str = '';
        foreach ($bytes as $byte) $str .= chr($byte);
        return $str;
    }

    /**
     * Convert double to string
     * @param double $num
     * @return string
     */
    public static function doubleToString($num)
    {
        return self::bytesToString(unpack('C8', pack('d', $num)));
    }

    /**
     * Convert string to double
     * @param string $str
     * @return double
     */
    public static function stringToDouble($str)
    {
        $data = unpack('d', pack('A8', $str));
        return $data[1];
    }

    /**
     * Convert bytes to integer
     * @param array $bytes
     * @return int
     */
    public static function bytesToInt(array $bytes)
    {
        return $bytes[3] << 24 | $bytes[2] << 16 | $bytes[1] << 8 | $bytes[0];
    }

    /**
     * Convert integer to bytes
     * @param $int
     * @return array
     */
    public static function intToBytes($int)
    {
        return [
            0xFF & $int >> 0,
            0xFF & $int >> 8,
            0xFF & $int >> 16,
            0xFF & $int >> 24
        ];
    }
}