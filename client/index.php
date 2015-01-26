<?php

define('ENV', 'web');
require_once('common.php');

$userId = $_GET['user'];
$user = $users[$userId];

$myFile = "./myfile.txt";
$myString="hello world\nhello world\nhello world\nhello world\nhello world\nhello world\nhello world\nhello world\nhello world\nhello world\n";
$fp = fopen($myFile, "a");
fwrite($fp, $myString);
fclose($fp);

?>
<!doctype html>
<html>
<head>
    <title>Picturnery</title>
    <link href="css/drawing.css" rel="stylesheet" type="text/css" />
    <link href="http://fonts.googleapis.com/css?family=Ubuntu:400,700,300italic" rel="stylesheet" type="text/css" />
</head>
<body>

<!-- Picturnery structure -->

<div class="container">
    <h1>Picturnery</h1>
    <div class="column-left">
        <div class="users"><ul id="user-list"></ul></div>
        <div class="guesses"><ul id="guess-list"></ul></div>
        <div class="guess">
            <input type="text" id="guess" placeholder="Enter guess" />
        </div>
    </div>
    <div class="column-right">
        <div class="drawing-container">
            <canvas id="drawing" width="600" height="560"></canvas>
        </div>
        <div class="round-information" id="round-information">
            <div class="waiting-for-players">Waiting for connection...</div>
        </div>
    </div>
</div>

<!-- Templates -->

<ul style="display: none" id="user-template">
    <li id="user-##id##">##username##<span class="score">##score##</span></li>
</ul>
<ul style="display: none" id="guess-template">
    <li><span class="username">##username##</span>##guess##</li>
</ul>

<div style="display: none" id="round-information-waiting-for-players">
    <div class="waiting-for-players">Waiting for players...</div>
</div>
<div style="display: none" id="round-information-guess-countdown">
    <div class="guess-countdown">
        <span class="drawer">Drawing by <b>##username##</b></span>
        <span class="countdown">Round ends in <b id="round-countdown-timer">##roundDuration##</b></span>
    </div>
</div>
<div style="display: none" id="round-information-draw-countdown">
    <div class="guess-countdown">
        <span class="drawer">Your word: <b>##word##</b></span>
        <span class="countdown">Round ends in <b id="round-countdown-timer">##roundDuration##</b></span>
    </div>
</div>
<div style="display: none" id="round-information-round-finished">
    <div class="round-finished">
        Round finished! The word <b>##word##</b> was guessed by <span class="guessers">##guessers##</span>
    </div>
</div>
<div style="display: none" id="round-information-round-finished-no-guessers">
    <div class="guess-countdown">
        Round finished! The word <b>##word##</b> was not guessed.
    </div>
</div>

<!-- Script -->

<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>
<script type="text/javascript" src="js/picturnery.js"></script>
<script type="text/javascript" src="js/drawing.js"></script>
<script type="text/javascript">
    $(function(){
        Picturnery.init({
            id: <?= $user->getId() ?>,
            hash: '<?= $user->getHash() ?>'
        }, {id: 0});
    });
</script>

</body>
</html>