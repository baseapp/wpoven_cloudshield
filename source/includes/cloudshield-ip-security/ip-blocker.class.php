<?php
require_once __DIR__ . '/vendor/autoload.php';

use MaxMind\Db\Reader;

class IPBlocker
{
    private $reader;
    private $searchEngineBots;
    private $aiCrawlers;

    public function __construct($databaseFilePath)
    {
        $this->reader = new Reader($databaseFilePath);

        $this->searchEngineBots = [
            'Googlebot',
            'Bingbot',
            'Slurp',
            'DuckDuckBot',
            'Baiduspider',
            'YandexBot',
            'Sogou',
            'Exabot'
        ];

        $this->aiCrawlers = [
            'GPTBot',
            'Google-Extended',
            'Bingbot',
            'BardBot',
            'CCBot',
            'Ai2bot',
            'ChatGPT-User',
            'anthropic-Bot',
            'ClaudeBot',
            'claude-web',
            'BingPreview',
            'Applebot',
            'YandexBot',
            'DuckDuckBot',
            'AhrefsBot',
            'SEMRushBot',
            'FacebookExternalHit',
            'MidjourneyBot',
            'StableDiffusionBot'
        ];
    }

    public function getClientIp()
    {
        return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    }

    public function getRequestUri()
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    private function isWhitelisted($visitorIp, $whiteListIP)
    {
        return in_array($visitorIp, $whiteListIP, true);
    }

    public function blockByCountry($visitorIp, $countriesList, $whiteListIP)
    {
        //if ($this->isWhitelisted($visitorIp, $whiteListIP)) return false;

        $countryDetails = $this->reader->get($visitorIp);
        if (!isset($countryDetails['country']['iso_code'])) return false;

        if (!in_array($countryDetails['country']['iso_code'], $countriesList, true)) {
            $this->denyAccess("Access from {$countryDetails['country']['iso_code']} is not allowed.");
        }
    }

    public function blockByIP($visitorIp, $ipList, $whiteListIP)
    {
        if ($this->isWhitelisted($visitorIp, $whiteListIP) || $visitorIp === $_SERVER['SERVER_ADDR']) return false;

        if (in_array($visitorIp, $ipList, true)) {
            $this->denyAccess("Access from {$visitorIp} is not allowed.");
        }
    }

    public function blockXmlRpc($requestURI)
    {
        if (str_contains($requestURI, 'xmlrpc.php')) {
            $this->denyAccess("XML-RPC access is not allowed.");
        }
    }

    public function blockNonSEO($visitorIp, $userAgent)
    {
        foreach ($this->searchEngineBots as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                $this->denyAccess("Access from {$visitorIp} is not allowed.");
            }
        }
    }

    public function blockAICrawlers($visitorIp, $userAgent)
    {
        foreach ($this->aiCrawlers as $crawler) {
            if (stripos($userAgent, $crawler) !== false) {
                $this->denyAccess("Access Denied: AI crawlers are not allowed.");
            }
        }
    }

    private function denyAccess($message)
    {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/html; charset=UTF-8');
        echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Access Denied</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; color: #343a40; text-align: center; }
        .container { margin-top: 250px; padding: 20px; background: white; display: inline-block; border-radius: 8px; }
        h1 { color: #dc3545; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Access Denied</h1>
        <p>{$message}</p>
    </div>
</body>
</html>";
        exit;
    }

    public function close()
    {
        $this->reader->close();
    }
}
