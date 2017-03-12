<?php

namespace Streams;

class BinaryStream extends Stream
{
    /**
     * @var bool
     */
    public $isLittleEndian = true;

    /**
     * @var string
     */
    public $charset;

    /**
     * Write array bytes
     * @param $bytes
     * @return int
     */
    public function writeBytes($bytes)
    {
        array_unshift($bytes, 'c*');
        return $this->write(call_user_func_array('pack', $bytes));
    }

    /**
     * Reads $length bytes from an input stream.
     * @param $length
     * @return array|false
     */
    public function readBytes($length)
    {
        $bytes = $this->read($length);
        if ($bytes !== false) {
            return array_values(unpack('c*', $bytes));
        }
        return false;
    }

    /**
     * Write string data
     * @param string $value
     * @param string|int $length
     * @param string $charset
     * @return int
     */
    public function writeString($value, $length = '*', $charset = null)
    {
        if ($charset) {
            $value = iconv('utf8', $charset, $value);
        } elseif ($this->charset) {
            $value = iconv('utf8', $this->charset, $value);
        }
        return $this->write(pack('A' . $length, $value));
    }

    /**
     * Read bytes as string
     * @param int $length
     * @param string $charset
     * @return string
     */
    public function readString($length, $charset = null)
    {
        $bytes = $this->read($length);
        $value = unpack('A' . $length, $bytes)[1];
        if ($charset) {
            $value = iconv($charset, 'utf8', $value);
        } elseif ($this->charset) {
            $value = iconv($this->charset, 'utf8', $value);
        }
        return $value;
    }

    /**
     * @param int|array $value
     * @param int $size
     * @return int
     */
    public function writeInt($value, $size = 32)
    {
        $bytes = Utils::intToBytes($value, $size);
        if (!$this->isLittleEndian) {
            $bytes = array_reverse($bytes);
        }
        array_unshift($bytes, 'C*');
        return $this->write(call_user_func_array('pack', $bytes));
    }

    /**
     * @param int $size
     * @param bool $unsigned
     * @return int
     */
    public function readInt($size = 32, $unsigned = true)
    {
        $size = Utils::roundUp($size, 8);
        $data = $this->read($size / 8);
        $value = 0;
        switch ($size) {
            case 8:
                $value = unpack('C', $data)[1];
                break;
            case 16:
                $value = unpack($this->isLittleEndian ? 'v' : 'n', $data)[1];
                break;
            case 24:
                $bytes = unpack('C3', $data);
                if ($this->isLittleEndian) {
                    $value = $bytes[1] | $bytes[2] << 8 | $bytes[3] << 16;
                } else {
                    $value = $bytes[1] << 16 | $bytes[2] << 8 | $bytes[3];
                }
                break;
            case 32:
                $value = unpack($this->isLittleEndian ? 'V' : 'N', $data)[1];
                break;
            case 64:
                $ret = unpack($this->isLittleEndian ? 'V2' : 'N2', $data);
                if ($this->isLittleEndian) {
                    $value = bcadd($ret[1], bcmul($ret[2], 0xffffffff + 1));
                } else {
                    $value = bcadd($ret[2], bcmul($ret[1], 0xffffffff + 1));
                }
                break;
        }
        return $unsigned ? $value : Utils::unsignedToSigned($value, $size);
    }

    /**
     * @param $value
     * @return int
     */
    public function writeBool($value)
    {
        return $this->writeInt($value ? 1 : 0, 8);
    }

    /**
     * @return int
     */
    public function readBool()
    {
        return $this->readInt(8);
    }

    /**
     * @param $value
     * @return int
     */
    public function writeFloat($value)
    {
        $bytes = pack('f', $value);
        return $this->write($bytes);
    }

    /**
     * @return int
     */
    public function readFloat()
    {
        $bytes = $this->read(4);
        return unpack('f', $bytes)[1];
    }

    /**
     * @param $value
     * @return int
     */
    public function writeDouble($value)
    {
        $bytes = pack('d', $value);
        return $this->write($bytes);
    }

    /**
     * @return int
     */
    public function readDouble()
    {
        $bytes = $this->read(8);
        return unpack('d', $bytes)[1];
    }

    /**
     * @param $length
     * @return int
     */
    public function writeNull($length)
    {
        return $this->write(pack('x' . $length));
    }
}