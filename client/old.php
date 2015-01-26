<?php /*

$addr = '192.168.93.128'; //gethostbyname("www.example.com");

$client = stream_socket_client("tcp://$addr:80", $errno, $errorMessage);

if ($client === false) {
    throw new UnexpectedValueException("Failed to connect: $errorMessage");
}
fwrite($client, "GET / HTTP/1.0\r\nHost: 192.168.93.128\r\nAccept: *"."/*\r\n\r\n");
echo stream_get_contents($client);
fclose($client);//*/ ?>
<!DOCTYPE html>
<html>
<head>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
    <title>WebSockets Client</title>
</head>
<body>
<div id="wrapper">

    <div id="container">

        <h1>WebSockets Client</h1>

        <div id="chatLog">

        </div><!-- #chatLog -->
        <p id="examples">e.g. try 'hi', 'name', 'age', 'today'</p>

        <input id="text" type="text" />
        <button id="disconnect">Disconnect</button>

    </div><!-- #container -->

</div>
<script type="text/javascript">
    $(document).ready(function() {
        if(!("WebSocket" in window)) {
            $('#chatLog, input, button, #examples').fadeOut("fast");
            $('<p>Oh no, you need a browser that supports WebSockets. How about <a href="http://www.google.com/chrome">Google/a>?</p>').appendTo('#container');
        } else {

            //The user has WebSockets

            connect();

            function connect(){
                try{
                    var host = "tcp://192.168.93.128:1337";
                    var socket = new WebSocket(host);

                    message('<p class="event">Socket Status: '+socket.readyState);

                    socket.onopen = function(){
                        message('<p class="event">Socket Status: '+socket.readyState+' (open)');
                    }

                    socket.onmessage = function(msg){
                        message('<p class="message">Received: '+msg.data);
                    }

                    socket.onclose = function(){
                        message('<p class="event">Socket Status: '+socket.readyState+' (Closed)');
                    }

                } catch(exception){
                    message('<p>Error'+exception);
                }
            }

            function message(msg){
                $('#chatLog').append(msg+'</p>');
            }
        }
    });
</script>
</body>
</html>​