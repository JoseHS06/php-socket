<?php

namespace App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class AppSocket implements MessageComponentInterface
{

    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        echo ("Socket is running \n");
    }

    public function onOpen(ConnectionInterface $conn)
    {
        //Almacena la conexión para enviar mensajes a través del socket
        $this->clients->attach($conn);
        echo ("New Connection:  {$conn->resourceId} \n");
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        try {

            $request = json_decode($msg);

            $response = [
                'from' => $from,
                'action' => $request->action,
                'payload' => $request->payload,
                'data' => $request->data
            ];

            $this->sendMessageToAll($from, $response);
            
        } catch (\Throwable $th) {
            //El error se envia al cliente que envió el mensaje al socket
            $this->sendErrorMessage($from, $th);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // Elimina a los clientes del socket
        $this->clients->detach($conn);
        echo ("Connection {$conn->resourceId} has disconected\n");
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        // Cierra la conexión del socket
        $conn->close();
        echo "Ocurrió un error al establecer conexión con el socket: {$e->getMessage()} \n";
    }

    public function sendMessageToAll($from, $response)
    {
        // Envia la respuesta a los clientes conectados
        foreach ($this->clients as $client) {
            $response['from'] = $from === $client;
            $client->send(json_encode($response));
        }
    }

    public function sendErrorMessage($from, $th)
    {
        // Envia la respuesta al cliente que lo solicitó
        foreach ($this->clients as $client) {
            if ($from === $client) {
                $client->send(json_encode([
                    'status' => 300,
                    'message' => "Ocurrió un error al realizar la petición: {$th}"
                ]));
            }
        }
        
    }
}
