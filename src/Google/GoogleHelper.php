<?php

declare(strict_types=1);

namespace Sptec\GmailApiMailerBundle\Google;

use Google\Exception;
use Google\Service\Gmail;
use Google\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class GoogleHelper
{
    public const TOKEN_CONST = 'GOOGLE_ACCESS_TOKEN';
    public const AUTH_CONST = 'GOOGLE_AUTH';

    public const USER = 'me';

    private Client $client;

    private string $redirectUri;

    private array $access_token;

    private string $auth_file;

    public function __construct(
        Client $client,
        string $redirectUri,
        array $access_token,
        string $auth_file
    ) {
        $this->redirectUri = $redirectUri;
        $this->access_token = $access_token;
        $this->client = $client;
        $this->auth_file = $auth_file;
        $this->setClientDefaults();
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getAuthenticatedClient(): Client
    {
        $this->client->setAccessToken($this->access_token);

        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                $this->writeAccessToken($this->client->getAccessToken());
            } else {
                throw new Exception('New authentication is required. Run bin/console sptec:gmail:auth');
            }
        }

        return $this->client;
    }

    public function writeAccessToken(array $accessToken): void
    {
        $accessToken = json_encode($accessToken, JSON_THROW_ON_ERROR);
        $path = $this->auth_file;
        if (!is_dir(dirname($path))) {
            if (!mkdir($concurrentDirectory = dirname($path), 0777, true) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }
        file_put_contents($path, $accessToken);
    }

    private function setClientDefaults(): void
    {
        $this->client->setApplicationName('Symfony Gmail API Mailer');
        $this->client->setRedirectUri($this->redirectUri);
        $this->client->setScopes(Gmail::GMAIL_SEND);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
    }
}
