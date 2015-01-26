<?php
# server.php

$server = stream_socket_server("tcp://127.0.0.1:1337", $errno, $errorMessage);

if ($server === false) {
    throw new UnexpectedValueException("Could not bind to socket: $errorMessage");
}

echo "Starting Server\n===============\n\n";

for (;;) {
    $client = @stream_socket_accept($server);

    if ($client) {
        stream_copy_to_stream($client, $client);
        fclose($client);
    }
}