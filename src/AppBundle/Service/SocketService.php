<?php


namespace AppBundle\Service;


use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;
use Exception;

class SocketService
{

    /** @var string */
    private $url;

    public function __construct($socketUrl)
    {
        $this->url = $socketUrl;
    }

    /**
     * @param string $eventType
     * @param string $login
     * @param string $message
     *
     * @return void
     */
    public function emit($eventType, $login, $message)
    {
        $client = new Client(new Version2X($this->url));

        try {
            $client->initialize();
        }
        catch (Exception $e) {}

        $client->emit('message', [
            "login" => $login,
            "type" => $eventType,
            "message" => $message
        ]);

        $client->close();
    }
}
