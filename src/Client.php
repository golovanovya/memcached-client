<?php

final class Client
{
    private \Socket $socket;

    public function __construct(string $address, int $port)
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            throw new \Exception("socket_create failed: reason: " . socket_strerror(socket_last_error()));
        }
        $result = socket_connect($socket, $address, $port);
        if ($result === false) {
            throw new \Exception(
                "socket_connect() failed: reason: ({$result}) "
                . socket_strerror(socket_last_error($socket))
            );
        }
        $this->socket = $socket;
    }

    public function __destruct()
    {
        socket_close($this->socket);
    }

    public function get(string $key, ?callable $default = null): ?string
    {
        $resp = socket_write($this->socket, "get {$key}\r\n");
        if ($resp === false) {
            throw new \Exception("can't write to socket: reason: " . socket_strerror(socket_last_error()));
        }
        $resp = socket_read($this->socket, 1024, PHP_NORMAL_READ);
        $exploded = explode(" ", $resp);
        if (
            $resp === false
            || count($exploded) < 4
            || ($exploded[0] ?? null) !== 'VALUE'
        ) {
            return $default ? $default() : null;
        }
        [, , , $length] = $exploded;
        socket_read($this->socket, 1);
        $val = socket_read($this->socket, $length);
        socket_read($this->socket, 1024);
        return $val;
    }

    public function set(string $key, string $value, int $expiration = 100): void
    {
        $bytes = strlen($value);
        $command = "set {$key} 0 {$expiration} {$bytes}\r\n{$value}\r\n";
        $resp = socket_write($this->socket, $command);
        if ($resp === false) {
            throw new \Exception("can't write to socket: reason: " . socket_strerror(socket_last_error()));
        }
        $out = socket_read($this->socket, 1024);
        if ($out !== "STORED\r\n") {
            throw new \Exception("value not stored: reason: " . socket_strerror(socket_last_error()));
        }
    }
}
