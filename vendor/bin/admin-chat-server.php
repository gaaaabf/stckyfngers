<?php

require dirname(__DIR__) . '/autoload.php';
require dirname(__DIR__) . '/../web/modules/custom/chatroom/src/AdminChatRoom.php';

use Drupal\chatroom\AdminChatRoom;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;

  // $server = IoServer::factory(
  //   new HttpServer(
  //     new WsServer(
  //       new AdminChatRoom()
  //     )
  //   ),
  //   80
  // );

  // $server->run();

  $server = IoServer::factory(
    new AdminChatRoom(),
    80
  );

  $server->run();