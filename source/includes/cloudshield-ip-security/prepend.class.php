<?php

require_once __DIR__ . '/ip-blocker.class.php';

// Path to the GeoLite2-Country.mmdb file
$databaseFilePath = __DIR__ . '/GeoLite2-Country.mmdb';

// Load JSON file
$jsonFilePath = __DIR__ . '/wpoven-cloudshield.json';
if (!file_exists($jsonFilePath)) {
    exit;
}

$jsonData = file_get_contents($jsonFilePath);
if ($jsonData === false) {
    exit;
}

$cloudshieldData = json_decode($jsonData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    exit;
}

// Extract settings and blocked IP data
$cloudshieldSettingData = $cloudshieldData['wpoven-cloudshield'] ?? [];
$cloudshieldBlockedIP = $cloudshieldData['blocked_ip_logs'] ?? [];
$whiteListIPString = $cloudshieldSettingData['cloudshield-cf-whitelist-ip'] ?? '';
$whiteListIP = array_map('trim', explode(',', $whiteListIPString));

// Create an instance of IPBlocker
$ipBlocker = new IPBlocker($databaseFilePath);

// Get visitor details
$visitorIp = $ipBlocker->getClientIp();
$requestURI = $ipBlocker->getRequestUri();
$userAgent = $ipBlocker->getUserAgent();

// Check if the visitor IP is already blocked and if it's been blocked for more than 30 minutes
date_default_timezone_set('UTC');
$currentTimestamp = time();
$blockedForTooLong = false;
$blockedIPAddresses = [];

foreach ($cloudshieldBlockedIP as $blocked) {
    if ($blocked['ip_address'] === $visitorIp && $blocked['ip_status'] === 'blocked') {
        $blockedTime = strtotime($blocked['blocked_at']);
        if (($currentTimestamp - $blockedTime) > 1800) { // 30 minutes
            $blockedForTooLong = true;
        } else {
            $blockedIPAddresses[] = $blocked['ip_address'];
        }
    }
}

// If the IP was blocked for more than 30 minutes, do nothing
if ($blockedForTooLong) {
    $ipBlocker->close();
    exit;
}

// Apply blocking rules based on settings
if ($cloudshieldSettingData) {

    // Block by country
    if (!empty($cloudshieldSettingData['cloudshield-cf-country-block'])) {
        $countriesList = $cloudshieldSettingData['cloudshield-country-list'] ?? [];
        if (!empty($countriesList)) {
            $ipBlocker->blockByCountry($visitorIp, $countriesList, $whiteListIP);
        }
    }

    // Block XML-RPC requests
    if (!empty($cloudshieldSettingData['cloudshield-cf-block-xmlrpc'])) {
        $ipBlocker->blockXmlRpc($visitorIp, $requestURI);
    }

    // Block failed login attempts
    if (!empty($cloudshieldSettingData['cloudshield-cf-wrong-login']) && !empty($blockedIPAddresses)) {
        $ipBlocker->blockByIP($visitorIp, $blockedIPAddresses, $whiteListIP);
    }

    /**
     * WPOven CloudShield DDoS Protection.
     */

    // Block by IP
    if (!empty($cloudshieldSettingData['cloudshield-cf-ip-block'])) {
        $ipList = $cloudshieldSettingData['cloudshield-ip-list'] ?? [];
        if (!empty($ipList)) {
            $ipBlocker->blockByIP($visitorIp, $ipList, $whiteListIP);
        }
    }

    /**
     * WPOven CloudShield Crawler Protection.
     */

    // Block non-SEO traffic
    if (!empty($cloudshieldSettingData['cloudshield-cf-block-non-seo'])) {
        $ipBlocker->blockNonSEO($visitorIp, $userAgent);
    }

    // Block AI Crawlers traffic
    if (!empty($cloudshieldSettingData['cloudshield-cf-block-ai-crawlers'])) {
        $ipBlocker->blockAICrawlers($visitorIp, $userAgent);
    }

    // Block failed 404 requests
    if (!empty($cloudshieldSettingData['cloudshield-cf-404-protection']) && !empty($blockedIPAddresses)) {
        $ipBlocker->blockByIP($visitorIp, $blockedIPAddresses, $whiteListIP);
    }
}

// Close the IPBlocker reader
$ipBlocker->close();
