--TEST--
swoole_http_client_coro: http download io failure
--SKIPIF--
<?php
require __DIR__ . '/../include/skipif.inc';
?>
--FILE--
<?php declare(strict_types = 1);
require __DIR__ . '/../include/bootstrap.php';

const FILE = '/tmp/download.html';

@unlink(FILE);
co::run(function () {
    $server = new OpenSwoole\Coroutine\Socket(AF_INET, SOCK_STREAM, IPPROTO_IP);
    Assert::assert($server->bind('127.0.0.1', 0));
    Assert::assert($server->listen());
    $oort = $server->getsockname()['port'];
    go(function () use ($server) {
        $client = $server->accept();
        while ($client->recv(1)) {
            co::usleep(10000);
        }
        $server->close();
    });
    $cli = new OpenSwoole\Coroutine\Http\Client('127.0.0.1', $oort);
    $cli->set(['timeout' => 0.1]);
    Assert::false($cli->download('/get', FILE));
    Assert::false(file_exists(FILE));
    echo "OK\n";
});
co::run(function () {
    $server = new OpenSwoole\Coroutine\Socket(AF_INET, SOCK_STREAM, IPPROTO_IP);
    Assert::assert($server->bind('127.0.0.1', 0));
    Assert::assert($server->listen());
    $oort = $server->getsockname()['port'];
    go(function () use ($server) {
        $client = $server->accept();
        $client->send("HTTP/1.1 200 OK\r\nContent-Length: 99999\r\n\r\n");
        while ($client->send('a')) {
            Co::usleep(1000);
        }
        $server->close();
    });
    $cli = new OpenSwoole\Coroutine\Http\Client('127.0.0.1', $oort);
    $cli->set(['timeout' => 0.1]);
    Assert::false($cli->download('/get', FILE));
    Assert::true(file_exists(FILE));
    echo "OK\n";
});
@unlink(FILE);

?>
--EXPECTF--
OK
OK
%sUnable to find callback function for signal Broken pipe: %d
