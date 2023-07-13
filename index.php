<?php

use App\AppSocket;

require __DIR__ . '/vendor/autoload.php';

$server = new Ratchet\App('localhost', 8282);

$server->route('/appSocket', new AppSocket());

$server->run();

?>