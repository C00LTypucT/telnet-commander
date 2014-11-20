<?php
namespace Avin\TelnetCommander;

use Exception;

class TelnetCommander
{

    private $server,
        $port,
        $socket,
        $commands = array(),
        $debug = false,
        $username = 'admin',
        $password = 'password',
        $hasAuth = true,
        $loginPromt = '/username:.*|login:.*/i',
        $passwordPromt = '/pass(word)?:.*/i',
        $shellPromt = '/#$/i';


    function __construct($server, $port = 23)
    {
        $this->setServer($server);
        $this->setPort($port);
        $this->setTimeout($port);
    }

    /**
     * Set socket IP
     *
     * @param mixed $server
     */
    private function setServer($server)
    {
        $this->server = $this->serverNameToIp($server);
    }

    /**
     * Translate server name to valid IP address
     * @param $server
     * @throws Exception
     */
    protected function serverNameToIp($server)
    {
        if (preg_match('/[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+/', $server)) {
            return $server;
        } else {
            $serverIp = gethostbyname($server);
            if ($serverIp != $server) {
                if (is_array($serverIp)) {
                    $serverIp = reset($serverIp);
                }
                return $serverIp;
            } else {
                throw new Exception('Could not resolve server hostname');
            }
        }
    }

    /**
     * Set socket port
     *
     * @param mixed $port
     */
    private function setPort($port)
    {
        if ($port > 0 && $port < 65534) {
            $this->port = $port;
        } else {
            throw new Exception("Port number is invalid");
        }
    }

    /**
     * @param mixed $timeout
     */
    public function setTimeout($timeout)
    {
        if (!is_numeric($timeout)) {
            throw new Exception('Timeout format incorrect');
        }
        $this->timeout = $timeout;
    }

    /**
     * Analyze incomming data
     * @param $data
     */
    public function processCommands($commands = array())
    {
        if ($commands) {
            $this->setCommands($commands);
        }
        $returnData = "";
        while ($data = socket_read($this->socket, 1024)) {
            if ($this->debug) {
                echo "$data";
            }
            $returnData .= $data;

            switch ($data) {
                case (preg_match($this->loginPromt, substr($data, -50)) ? true : false) :
                    if ($this->hasAuth) {
                        $this->sendData($this->username);
                        break;
                    }
                case (preg_match($this->passwordPromt, substr($data, -50)) ? true : false) :
                    if ($this->hasAuth) {
                        $this->sendData($this->password);
                        break;
                    }
                case (preg_match($this->shellPromt, substr($data, -50)) ? true : false) :
                    //If no more commands - exit
                    if (!$this->hasCommands()) {
                        $this->disconnect();
                        return $returnData;
                    }
                    //else execute command
                    $this->sendData($this->currentCommand());
                    break;
            }
        }
    }

    /**
     * Set commands list
     * @param $commands
     */
    public function setCommands($commands)
    {
        if (is_array($commands)) {
            if (!$commands) {
                throw new Exception("Empty command-list. Exit");
            }
            if (is_array($commands[0])) {
                if (array_key_exists('promt', $commands[0])) {
                    $this->setShellPromt($commands[0]['promt']);
                }
            }
            $this->commands = $commands;
        } else {
            throw new Exception("Wrong command-list format");
        }
    }

    /**
     * Send the message to the server
     *
     * @param $sock
     * @param $message
     */
    private function sendData($message)
    {
        if ($this->debug) {
            echo "Sending data[{$message}]... ";
        }
        $message = $message . "\r\n";
        if (!socket_send($this->socket, $message, strlen($message), 0)) {
            throw new Exception("Could not send data. Reason: " . socket_strerror(socket_last_error()));
        }
    }

    /**
     * Check if any commands presents
     *
     * @return bool
     */
    private function hasCommands()
    {
        if ($this->commands) {
            return true;
        }

        return false;
    }

    private function disconnect()
    {
        socket_close($this->socket);
    }

    /**
     * Get current command
     */
    private function currentCommand()
    {
        //If no more commands in list
        if (!$this->commands) {
            return false;
        }

        $command = array_shift($this->commands);
        if (is_array($command)) {
            $currentCommand = $command['command'];
            if (array_key_exists('promt', $command)) {
                $this->setShellPromt($command['promt']);
            }
        } else {
            $currentCommand = $command;
        }

        //Setup shell-promt for next command
        if ($commands = $this->commands) {
            $command = array_shift($commands);
            if (is_array($command)) {
                if (array_key_exists('promt', $command)) {
                    $this->setShellPromt($command['promt']);
                }
            }
        }

        return $currentCommand;
    }

    /**
     * Set custom shell promt
     *
     * @param string $shellPromt
     */
    public function setShellPromt($shellPromt)
    {
        $this->shellPromt = $shellPromt;
    }

    /**
     * Connect to server
     *
     * @throws Exception
     */
    public function connect()
    {
        if ($this->debug) {
            echo "Connection to [{$this->server}:{$this->port}]... ";
        }
        $this->createSocket();
        $result = socket_connect($this->socket, $this->server, $this->port);
        if ($result === false) {
            throw new Exception("Could not execute socket_connect(): Reason: " . socket_strerror(socket_last_error()));
        }
    }

    /**
     * Create  TCP/IP socket.
     *
     * @return bool
     */
    private function createSocket()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) {
            throw new Exception("Could not execute socket_create(): Reason: " . socket_strerror(socket_last_error()));
        }
    }

    /**
     * @param boolean $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * Set custom login promt
     *
     * @param string $loginPromt
     */
    public function setLoginPromt($loginPromt)
    {
        $this->loginPromt = $loginPromt;
    }

    /**
     *  Set custom password promt
     *
     * @param string $passwordPromt
     */
    public function setPasswordPromt($passwordPromt)
    {
        $this->passwordPromt = $passwordPromt;
    }

    /**
     * Set if auth present on server
     *
     * @param boolean $hasAuth
     */
    public function setHasAuth($hasAuth)
    {
        $this->hasAuth = $hasAuth;
    }

    /**
     * Setup login username
     *
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Setup login password
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }
}
