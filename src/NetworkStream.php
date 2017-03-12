<?php

namespace Streams;

class NetworkStream extends Stream
{
    /**
     * Retrieve the name of the local or remote sockets
     * @param bool $remote
     * @return string
     */
    public function getName($remote = true)
    {
        return stream_socket_get_name($this->_stream, $remote);
    }
}