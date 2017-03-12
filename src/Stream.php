<?php

namespace Streams;

class Stream
{
    /**
     * @var array
     */
    public $options = [
        'default_stream' => 'php://temp',
        'default_stream_mode' => 'r+',
    ];

    /**
     * @var resource
     */
    protected $_stream;

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
        $this->_stream = $stream;
        $this->options = array_merge($this->options, $options);
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
        return stream_get_meta_data($this->_stream);
    }

    /**
     * Get stream resource
     * @return resource
     */
    public function getResource()
    {
        return $this->_stream;
    }

    /**
     * Get stream size
     * @return int
     */
    public function size()
    {
        $currPos = ftell($this->_stream);
        fseek($this->_stream, 0, SEEK_END);
        $length = ftell($this->_stream);
        fseek($this->_stream, $currPos, SEEK_SET);
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
        if (stream_copy_to_stream($this->_stream, $stream, $length)) {
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
            return stream_copy_to_stream($resource, $this->_stream, $length);
        } else {
            return stream_copy_to_stream($resource, $this->_stream);
        }
    }

    /**
     * Reads remainder of a stream into a string
     * @param int $length The maximum bytes to read. Defaults to -1 (read all the remaining buffer).
     * @return string a string or false on failure.
     */
    public function read($length = null)
    {
        return stream_get_contents($this->_stream, $length, $this->offset());
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
        return stream_get_line($this->_stream, $length, $ending);
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
            return fwrite($this->_stream, $data);
        } else {
            return fwrite($this->_stream, $data, $length);
        }
    }

    /**
     * Returns the current position of the file pointer
     * @return int
     */
    public function offset()
    {
        return ftell($this->_stream);
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
        return fseek($this->_stream, $offset, $whence);
    }

    /**
     * Rewind the position of a file pointer
     * @return bool true on success or false on failure.
     */
    public function rewind()
    {
        return rewind($this->_stream);
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
     * Save stream
     * @param string $file Path to the file where to write the data.
     * @return int The function returns the number of bytes that were written to the file, or false on failure.
     */
    public function save($file)
    {
        $this->rewind();
        return file_put_contents($file, $this->_stream);
    }

    /**
     * @return void
     */
    public function close()
    {
        if (is_resource($this->_stream)) {
            fclose($this->_stream);
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}