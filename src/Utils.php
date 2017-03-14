<?php

namespace ByteBuffer;

class Utils
{
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
     * @param string $str
     * @return double
     * @throws \Exception
     */
    public static function stringToDouble($str)
    {
        if (strlen($str) < 8) {
            throw new \Exception('String must be a 8 length');
        }
        return unpack('d', pack('A8', $str))[1];
    }

    /**
     * @param array $bytes
     * @param bool $unsigned
     * @return int
     */
    public static function bytesToInt(array $bytes, $unsigned = true)
    {
        $bytes = array_reverse($bytes);
        $value = 0;
        foreach ($bytes as $i => $b) {
            $value |= $b << $i * 8;
        }
        return $unsigned ? $value : self::unsignedToSigned($value, count($bytes) * 8);
    }

    /**
     * @param $int
     * @param int $size
     * @return array
     */
    public static function intToBytes($int, $size = 32)
    {
        $size = self::roundUp($size, 8);
        $bytes = [];
        for ($i = 0; $i < $size; $i += 8) {
            $bytes[] = 0xFF & $int >> $i;
        }
        $bytes = array_reverse($bytes);
        return $bytes;
    }

    /**
     * @param int $value
     * @param int $size
     * @return string
     */
    public static function unsignedToSigned($value, $size = 32)
    {
        $size = self::roundUp($size, 8);
        if (bccomp($value, bcpow(2, $size - 1)) >= 0) {
            $value = bcsub($value, bcpow(2, $size));
        }
        return $value;
    }

    /**
     * @param int $value
     * @param int $size
     * @return string
     */
    public static function signedToUnsigned($value, $size = 32)
    {
        return $value + bcpow(2, $size);
    }
}