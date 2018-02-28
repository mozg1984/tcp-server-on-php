<?php

namespace App;

abstract class TCPSocket
{
    /**
     * @var resource Socket descriptor
     */
    protected $socket;

    /**
     * Opens socket
     */
    public function open()
    {

    }

    /**
     * Closes socket
     */
    public function close()
    {
        @socket_shutdown($this->socket);
        @socket_close($this->socket);
        $this->socket = null;
    }

    /**
     * Checks if socket descriptor is opened
     *
     * @return bool
     */
    public function isSocketOpened(): bool
    {
        return $this->socket != null && is_resource($this->socket);
    }
}