<?php

namespace App;

use App\Exceptions\ServerTCPSocketException;

class ServerTCPSocket extends TCPSocket
{
    /**
     * @var string Address
     */
    private $address;
    
    /**
     * @var int Port
     */
    private $port;
    
    /**
     * @var bool Flag of listening client connections
     */
    private $isListening = false;
    
    /**
     * @var array Array of child id processes
     */
    private $childProcessIds = [];

    /**
     * @static int Number of maximum connections
     */
    public static $MAX_CLIENT_COUNT = 5;
    
    /**
     * @var bool Number of possible expected connections
     */
    public static $BACKLOG = 5;

    /**
     * @static int Delay between processing of child processes (ms)
     */
    public static $DELAY = 100;

    /**
     * ServerTCPSocket constructor
     *
     * @throws ServerTCPSocketException When something goes wrong with creating socket
     */
    public function __construct()
    {
        $this->open();
        
        // Adds handlers on signals
        $this->addSignalHandler(SIGHUP, [$this, "stopListening"]);
        $this->addSignalHandler(SIGCHLD, [$this, "handleChildProcesses"]);
    }

    /**
     * Creates socket (in nonblocking mode)
     *
     * @throws ServerTCPSocketException When something goes wrong with creating socket
     */
    public function open()
    {
        $this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (!$this->socket) {
            throw new ServerTCPSocketException('Unable to create TCP socket server');
        }

        @socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        @socket_set_nonblock($this->socket);
    }

    /**
     * Binds address and port to socket
     *
     * @param string $address
     * @param int $port
     *
     * @throws ServerTCPSocketException When socket is not created or when can not bind address and port
     */
    public function bind(string $address, int $port)
    {
        if (!$this->isSocketOpened()) {
            throw new ServerTCPSocketException('TCP socket server is not created');
        }

        $this->address = $address;
        $this->port = $port;

        $result = @socket_bind(
            $this->socket, 
            $this->address, 
            $this->port
        );

        if (!$result) {
            throw new ServerTCPSocketException(
                sprintf("TCP socket server: can not bind '%s':'%s'", $this->address, $this->port)
            );
        }

        return $this;
    }

    /**
     * Start to listen client connections
     *
     * All client connections handles in forked process.
     * All client connections are wrapped to ClientTCPSocket
     *
     * @throws ServerTCPSocketException When socket is not created or when can not start listening
     */
    public function listen()
    {
        if (!$this->isSocketOpened()) {
            throw new ServerTCPSocketException('TCP socket server is not created');
        }

        $result = @socket_listen($this->socket, self::$BACKLOG);

        if (!$result) {
            throw new ServerTCPSocketException('TCP socket server: can not start listening'); 
        }

        $this->isListening = true;

        while ($this->isListening) {
            $clientTCPSocketResource = @socket_accept($this->socket);

            if (is_resource($clientTCPSocketResource) && !$this->isTheClientsLimitReached()) {
                $clientTCPSocket = new ClientTCPSocket($clientTCPSocketResource);
                $this->handleClientTCPSocket($clientTCPSocket);
            }
            
            $this->signalDispatch();

            usleep(self::$DELAY);
        }

        $this->releaseAllChildProcesses();
    }

    /**
     * Stop to listen client connections
     *
     * @return ServerTCPSocket
     */
    public function stopListening()
    {
        $this->isListening = false;

        return $this;
    }

    /**
     * Checks is the client connections limit reached
     *
     * @return bool
     */
    public function isTheClientsLimitReached(): bool
    {
        return count($this->childProcessIds) >= self::$MAX_CLIENT_COUNT;
    }

    /**
     * Adds signal handler
     *
     * @param int $SIGNAL The signal number 
     * @param Callable $signalHandler Signal handler
     */
    public function addSignalHandler(int $SIGNAL, Callable $signalHandler)
    {
        pcntl_signal($SIGNAL, $signalHandler);
    }

    /**
     * Dispatches all signals
     */
    private function signalDispatch()
    {
        pcntl_signal_dispatch();
    }

    /**
     * handleClientTCPSocket
     *
     * @param ClientTCPSocket $clientTCPSocket 
     */
    private function handleClientTCPSocket(ClientTCPSocket $clientTCPSocket)
    {
        $childProcessId = pcntl_fork();

        if ($childProcessId > 0) {
            $this->childProcessIds[] = $childProcessId;
            return;
        }

        try {
            $clientTCPSocket->write("\nWelcome to the PHP Test Server. \n");

            while (true) {
                $buffer = $clientTCPSocket->read();
                
                if ($buffer == 'quit' || $buffer === false) {
                    $clientTCPSocket->close();
                    break;
                }

                if ($buffer !== '') {
                    $talkback = sprintf("PHP: You said %s\n", $buffer);
                    $clientTCPSocket->write($talkback);
                    print sprintf(": %s\n", $buffer);
                }
            }
        } catch (\Exception $exception) {
            $clientTCPSocket->close();
            exit(1);
        }               
        
        exit(0);
    }

    /**
     * Releases all child processes (read their statuses)
     */
    private function releaseAllChildProcesses()
    {
        while ($this->hasChildProcesses()) {
            $this->handleChildProcesses();
            usleep(self::$DELAY);
        }
    }

    /**
     * Handles all child processes
     *
     * Reads exit statuses of all child processes. 
     */
    public function handleChildProcesses()
    {
        foreach ($this->childProcessIds as $key => $childProcessId) {
            $result = pcntl_waitpid($childProcessId, $status, WNOHANG);

            if ($result == -1 || $result > 0) {
                unset($this->childProcessIds[$key]);
            }
        }
    }

    /**
     * Checks is server has child processes
     *
     * @return bool
     */
    public function hasChildProcesses(): bool
    {
        return !empty($this->childProcessIds);
    }
}