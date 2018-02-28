<?php

namespace App;

use App\Exceptions\ClientTCPSocketException;

class ClientTCPSocket extends TCPSocket
{
    /**
     * ClientTCPSocket constructor
     *
     * @param resource $clientTCPSocketResource
     */
    public function __construct($clientTCPSocketResource)
    {
        $this->socket = $clientTCPSocketResource;
    }

    /**
     * Reads data from socket
     *
     * @param int $length Count of bytes to read (default = 2048)
     * @param int $type Reading type (default = PHP_BINARY_READ)
     *
     * @throws ClientTCPSocketException When socket is not opened
     */
    public function read(int $length = 2048, int $type = PHP_BINARY_READ)
    {
        if (!$this->isSocketOpened()) {
            throw new ClientTCPSocketException('');
        }

        $buffer = @socket_read($this->socket, $length, $type);
        
        return trim($buffer);
    }

    /**
     * Writes data to socket
     *
     * @param string $message Message
     *
     * @throws ClientTCPSocketException When socket is not opened
     */
    public function write(string $message)
    {
        if (!$this->isSocketOpened()) {
            throw new ClientTCPSocketException('');
        }

        @socket_write($this->socket, $message, strlen($message));

        return $this;
    }
}