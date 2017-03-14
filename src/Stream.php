<?php

namespace ByteBuffer;

class Stream
{
    /**
     * @var array
     */
    public $options = [];

    /**
     * @var bool
     */
    public $isLittleEndian = true;

    /**
     * @var resource
     */
    protected $_handle;

    /**
     * Stream constructor.
     * @param resource $stream Stream resource to wrap.
     * @param array $options Associative array of options.
     */
    protected function __construct($stream, $options = [])
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a resource');
        }
        $this->_handle = $stream;
        $this->options = $options;
    }

    /**
     * Create a new stream based on the input type.
     * @param mixed $resource Entity body data
     * @param array $options Additional options
     * @return static
     */
    public static function factory($resource = '', $options = [])
    {
        $type = gettype($resource);
        switch ($type) {
            case 'string':
                $stream = fopen('php://temp', 'r+');
                if ($resource !== '') {
                    fwrite($stream, $resource);
                    fseek($stream, 0);
                }
                return new static($stream, $options);
            case 'resource':
                return new static($resource, $options);
            case 'object':
                if (method_exists($resource, '__toString')) {
                    return static::factory((string)$resource, $options);
                }
        }
        throw new \InvalidArgumentException(sprintf('Invalid resource type: %s', $type));
    }

    /**
     * Get stream meta data
     * @return array
     */
    public function getMetaData()
    {
        return stream_get_meta_data($this->_handle);
    }

    /**
     * Get stream resource
     * @return resource
     */
    public function getResource()
    {
        return $this->_handle;
    }

    /**
     * Get stream size
     * @return int
     */
    public function size()
    {
        $currPos = ftell($this->_handle);
        fseek($this->_handle, 0, SEEK_END);
        $length = ftell($this->_handle);
        fseek($this->_handle, $currPos, SEEK_SET);
        return $length;
    }

    /**
     * Allocate new stream from current stream
     * @param int $length
     * @param bool $skip
     * @return static
     * @throws Exception
     */
    public function allocate($length, $skip = true)
    {
        $stream = fopen('php://memory', 'r+');
        if (stream_copy_to_stream($this->_handle, $stream, $length)) {
            if ($skip) {
                $this->skip($length);
            }
            return new static($stream);
        }
        throw new Exception('Buffer allocation failed');
    }

    /**
     * Copies data from $resource to stream
     * @param resource $resource
     * @param int $length Maximum bytes to copy
     * @return int
     */
    public function pipe($resource, $length = null)
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('Invalid resource type');
        }
        if ($length) {
            return stream_copy_to_stream($resource, $this->_handle, $length);
        } else {
            return stream_copy_to_stream($resource, $this->_handle);
        }
    }

    /**
     * Returns the current position of the file pointer
     * @return int
     */
    public function offset()
    {
        return ftell($this->_handle);
    }

    /**
     * Move the file pointer to a new position
     * @param int $offset
     * @param int $whence Accepted values are:
     *  - SEEK_SET - Set position equal to $offset bytes.
     *  - SEEK_CUR - Set position to current location plus $offset.
     *  - SEEK_END - Set position to end-of-file plus $offset.
     * @return int
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        return fseek($this->_handle, $offset, $whence);
    }

    /**
     * Rewind the position of a file pointer
     * @return bool true on success or false on failure.
     */
    public function rewind()
    {
        return rewind($this->_handle);
    }

    /**
     * @param int $length
     * @return int
     */
    public function skip($length)
    {
        return $this->seek($length, SEEK_CUR);
    }

    /**
     * Reads remainder of a stream into a string
     * @param int $length The maximum bytes to read. Defaults to -1 (read all the remaining buffer).
     * @return string a string or false on failure.
     */
    public function read($length = null)
    {
        return stream_get_contents($this->_handle, $length, $this->offset());
    }

    /**
     * Read one line from the stream.
     * @param int $length Maximum number of bytes to read.
     * @param string $ending Line ending to stop at. Defaults to "\n".
     * @return string The data read from the stream
     */
    public function readLine($length = null, $ending = "\n")
    {
        if ($length === null) {
            $length = $this->size();
        }
        return stream_get_line($this->_handle, $length, $ending);
    }

    /**
     * Write data to stream.
     * @param string $data
     * @param int $length
     * @return int
     */
    public function write($data, $length = null)
    {
        if ($length === null) {
            return fwrite($this->_handle, $data);
        } else {
            return fwrite($this->_handle, $data, $length);
        }
    }

    /**
     * Write array bytes
     * @param $bytes
     * @return int
     */
    public function writeBytes($bytes)
    {
        array_unshift($bytes, 'C*');
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
            return array_values(unpack('C*', $bytes));
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
        } elseif (isset($this->options['charset'])) {
            $value = iconv('utf8', $this->options['charset'], $value);
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
        } elseif ($this->options['charset']) {
            $value = iconv($this->options['charset'], 'utf8', $value);
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

    /**
     * Save stream
     * @param string $file Path to the file where to write the data.
     * @return int The function returns the number of bytes that were written to the file, or false on failure.
     */
    public function save($file)
    {
        $this->rewind();
        return file_put_contents($file, $this->_handle);
    }

    /**
     * @return void
     */
    public function close()
    {
        if (is_resource($this->_handle)) {
            fclose($this->_handle);
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}