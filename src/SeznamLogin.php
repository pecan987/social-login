<?php

namespace Vencax;

use Nette;

class SeznamLogin extends BaseLogin
{
    const SOCIAL_NAME = "seznam";

    const AUTH_URL = 'https://login.szn.cz/api/v1/oauth/auth';
    const TOKEN_URL = 'https://login.szn.cz/api/v1/oauth/token';
    const USER_URL = 'https://login.szn.cz/api/v1/user';

    // Konstanty pro pole uživatele
    const OAUTH_USER_ID = 'oauth_user_id';
    const EMAIL = 'email';
    const FIRSTNAME = 'firstname';
    const LASTNAME = 'lastname';

    private array $scope = ['identity'];
    private ?string $state = null;

    public function __construct($params, $cookieName, Nette\Http\Response $httpResponse, Nette\Http\Request $httpRequest)
    {
        $this->params = $params;
        $this->cookieName = $cookieName;
        $this->httpResponse = $httpResponse;
        $this->httpRequest = $httpRequest;
    }

    public function setScope(array $scope): void
    {
        $this->scope = $scope;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getLoginUrl(): string
    {
        $params = [
            'client_id' => $this->params['clientId'],
            'redirect_uri' => $this->params['callbackURL'],
            'response_type' => 'code',
            'scope' => implode(',', $this->scope),
        ];

        if ($this->state) {
            $params['state'] = $this->state;
        }

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    public function getMe(string $code): array
    {
        // 1. Výměna code za access token
        $tokenData = $this->getAccessToken($code);

        // 2. Získání user info
        $userData = $this->getUserInfo($tokenData['access_token']);

        $this->setSocialLoginCookie(self::SOCIAL_NAME);

        return $userData;
    }

    private function getAccessToken(string $code): array
    {
        $postData = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->params['callbackURL'],
            'client_id' => $this->params['clientId'],
            'client_secret' => $this->params['clientSecret'],
        ];

        $ch = curl_init(self::TOKEN_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Seznam OAuth token request failed: ' . $response);
        }

        return json_decode($response, true);
    }

    private function getUserInfo(string $accessToken): array
    {
        $ch = curl_init(self::USER_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: bearer ' . $accessToken,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Seznam OAuth user info request failed: ' . $response);
        }

        return json_decode($response, true);
    }

    public function isThisServiceLastLogin(): bool
    {
        return $this->getSocialLoginCookie() === self::SOCIAL_NAME;
    }
}
