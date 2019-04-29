<?php


namespace AppBundle\Service;


use AppBundle\Security\User;
use GuzzleHttp\Client;

class TwitchService
{
    private $client_id;
    private $client_secret;
    private $redirect_url;
    private $webhook_url;
    private $scopes = "user:read:email";
    private $httpClient;

    public function __construct($client_id, $client_secret, $redirect_url, $webhook_url)
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->redirect_url = $redirect_url;
        $this->webhook_url = $webhook_url;

        $this->httpClient = new Client();
    }

    public function getAuthorizationURI() {
        return "https://id.twitch.tv/oauth2/authorize" .
                "?client_id={$this->client_id}" .
                "&response_type=code" .
                "&scope={$this->scopes}" .
                "&redirect_uri={$this->redirect_url}";
    }

    /**
     * @param string $code
     * @return User
     */
    public function authorize($code) {
        $params = [
            "query"=> [
                "client_id" => $this->client_id,
                "client_secret" => $this->client_secret,
                "code" => $code,
                "grant_type" => "authorization_code",
                "redirect_uri" => $this->redirect_url,
            ]
        ];

        $uri = "https://id.twitch.tv/oauth2/token";

        $response = $this->httpClient->post($uri, $params);

        $body = $response->getBody();
        $obj = json_decode($body);

        return $this->getUser($obj->access_token);
    }

    /**
     * @param string $name
     * @param User $user
     * @return mixed
     */
    public function getStreamerId($name, $user) {
        $params = [
            "headers" => [
                "Authorization" => "Bearer {$user->getToken()}"
            ],
            "query" => [
                "login" => $name
            ]
        ];

        $uri = "https://api.twitch.tv/helix/users";

        $response = $this->httpClient->get($uri, $params);

        $body = $response->getBody();
        $obj = json_decode($body);

        return $obj->data[0]->id;
    }

    /**
     * @param User $user
     * @return mixed
     */
    public function subscribe($user) {
        $params = [
            "headers" => [
                "Authorization" => "Bearer {$user->getToken()}"
            ],
            "json" => null
        ];

        $uri = 'https://api.twitch.tv/helix/webhooks/hub';

        $this->subscribeFollow($user, $uri, $params);
        $this->subscribeFollower($user, $uri, $params);
        $this->subscribeStream($user, $uri, $params);
        $this->subscribeUser($user, $uri, $params);

        return true;
    }

    /**
     * @param User $user
     * @return mixed
     */
    public function unsubscribe($user) {
        $params = [
            "headers" => [
                "Authorization" => "Bearer {$user->getToken()}"
            ],
            "json" => null
        ];

        $uri = 'https://api.twitch.tv/helix/webhooks/hub';

        $this->unSubscribeFollow($user, $uri, $params);
        $this->unSubscribeFollower($user, $uri, $params);
        $this->unSubscribeStream($user, $uri, $params);
        $this->unSubscribeUser($user, $uri, $params);

        return true;
    }

    /**
     * @param string $accessToken
     * @return User
     */
    private function getUser($accessToken) {
        $params = [
            "headers" => [
                "Authorization" => "Bearer {$accessToken}"
            ]
        ];

        $uri = "https://api.twitch.tv/helix/users";

        $response = $this->httpClient->get($uri, $params);

        $body = $response->getBody();
        $obj = json_decode($body);

        $user = new User($obj->data[0]->login, $accessToken);

        return $user;
    }

    /**
     * @param User $user
     * @return mixed
     */
    private function subscribeFollow($user, $uri, $params) {
        $params["json"] = [
            "hub.callback"      => "{$this->webhook_url}follow",
            "hub.mode"          => "subscribe",
            "hub.topic"         => "https://api.twitch.tv/helix/users/follows?first=1&from_id={$user->getStreamer()}",
            "hub.lease_seconds" => 864000,
        ];

        return $this->httpClient->post($uri, $params);
    }

    /**
     * @param User $user
     * @return mixed
     */
    private function unSubscribeFollow($user, $uri, $params) {
        $params["json"] = [
            "hub.callback"      => "{$this->webhook_url}follow",
            "hub.mode"          => "unsubscribe",
            "hub.topic"         => "https://api.twitch.tv/helix/users/follows?first=1&from_id={$user->getStreamer()}",
        ];

        return $this->httpClient->post($uri, $params);
    }

    /**
     * @param User $user
     * @return mixed
     */
    private function subscribeFollower($user, $uri, $params) {
        $params["json"] = [
            "hub.callback"      => "{$this->webhook_url}follower",
            "hub.mode"          => "subscribe",
            "hub.topic"         => "https://api.twitch.tv/helix/users/follows?first=1&to_id={$user->getStreamer()}",
            "hub.lease_seconds" => 864000,
        ];

        return $this->httpClient->post($uri, $params);
    }

    /**
     * @param User $user
     * @return mixed
     */
    private function unSubscribeFollower($user, $uri, $params) {
        $params["json"] = [
            "hub.callback"      => "{$this->webhook_url}follower",
            "hub.mode"          => "unsubscribe",
            "hub.topic"         => "https://api.twitch.tv/helix/users/follows?first=1&to_id={$user->getStreamer()}",
        ];

        return $this->httpClient->post($uri, $params);
    }

    /**
     * @param User $user
     * @return mixed
     */
    private function subscribeStream($user, $uri, $params) {
        $params["json"] = [
            "hub.callback"      => "{$this->webhook_url}stream",
            "hub.mode"          => "subscribe",
            "hub.topic"         => "https://api.twitch.tv/helix/streams?user_id={$user->getStreamer()}",
            "hub.lease_seconds" => 864000,
        ];

        return $this->httpClient->post($uri, $params);
    }

    /**
     * @param User $user
     * @return mixed
     */
    private function unSubscribeStream($user, $uri, $params) {
        $params["json"] = [
            "hub.callback"      => "{$this->webhook_url}stream",
            "hub.mode"          => "unsubscribe",
            "hub.topic"         => "https://api.twitch.tv/helix/streams?user_id={$user->getStreamer()}",
        ];

        return $this->httpClient->post($uri, $params);
    }

    /**
     * @param User $user
     * @return mixed
     */
    private function subscribeUser($user, $uri, $params) {
        $params["json"] = [
            "hub.callback"      => "{$this->webhook_url}user",
            "hub.mode"          => "subscribe",
            "hub.topic"         => "https://api.twitch.tv/helix/users?id={$user->getStreamer()}",
            "hub.lease_seconds" => 864000,
        ];

        return $this->httpClient->post($uri, $params);
    }

    /**
     * @param User $user
     * @return mixed
     */
    private function unSubscribeUser($user, $uri, $params) {
        $params["json"] = [
            "hub.callback"      => "{$this->webhook_url}user",
            "hub.mode"          => "unsubscribe",
            "hub.topic"         => "https://api.twitch.tv/helix/users?id={$user->getStreamer()}",
        ];

        return $this->httpClient->post($uri, $params);
    }
}