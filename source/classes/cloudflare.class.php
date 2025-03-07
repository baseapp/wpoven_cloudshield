<?php

defined('ABSPATH') || die('Cheating&#8217; uh?');

class CloudShield_Cloudflare
{

    private $main_instance = null;

    private $objects   = false;
    private $api_key   = '';
    private $email     = '';
    private $api_token = '';
    private $auth_mode = 0;
    private $zone_id   = '';
    private $api_token_domain = '';


    function __construct($auth_mode, $api_key, $email, $api_token, $zone_id, $main_instance)
    {

        $this->auth_mode       = $auth_mode;
        $this->api_key         = $api_key;
        $this->email           = $email;
        $this->api_token       = $api_token;
        $this->zone_id         = $zone_id;
        $this->main_instance   = $main_instance;
    }

    function set_auth_mode($auth_mode)
    {
        $this->auth_mode = $auth_mode;
    }


    function set_api_key($api_key)
    {
        $this->api_key = $api_key;
    }


    function set_api_email($email)
    {
        $this->email = $email;
    }

    function set_api_token($api_token)
    {
        $this->api_token = $api_token;
    }


    function set_api_token_domain($api_token_domain)
    {
        $this->api_token_domain = $api_token_domain;
    }

    function get_api_headers($standard_curl = false)
    {

        $cf_headers = array();
        if ($this->auth_mode == CLOUDSHIELD_AUTH_MODE_API_TOKEN) {

            if ($standard_curl) {

                $cf_headers = array(
                    'headers' => array(
                        "Authorization: Bearer {$this->api_token}",
                        'Content-Type: application/json'
                    )
                );
            } else {

                $cf_headers = array(
                    'headers' => array(
                        'Authorization' => "Bearer {$this->api_token}",
                        'Content-Type' => 'application/json'
                    )
                );
            }
        } else {

            if ($standard_curl) {

                $cf_headers = array(
                    'headers' => array(
                        "X-Auth-Email: {$this->email}",
                        "X-Auth-Key: {$this->api_key}",
                        'Content-Type: application/json'
                    )
                );
            } else {

                $cf_headers = array(
                    'headers' => array(
                        'X-Auth-Email' => $this->email,
                        'X-Auth-Key' => $this->api_key,
                        'Content-Type' => 'application/json'
                    )
                );
            }
        }

        $cf_headers['timeout'] = defined('CLOUDSHIELD_CURL_TIMEOUT') ? CLOUDSHIELD_CURL_TIMEOUT : 10;

        return $cf_headers;
    }

    /**
     * Get all cloudflare zone ids list.
     */
    function get_zone_id_list(&$error)
    {
        $this->objects = $this->main_instance->get_objects();

        $zone_id_list = array();
        $per_page     = 50;
        $current_page = 1;
        $pagination   = false;
        $cf_headers   = $this->get_api_headers();
        do {

            if ($this->auth_mode == CLOUDSHIELD_AUTH_MODE_API_TOKEN && $this->api_token_domain != '') {
                $response = wp_remote_get(
                    esc_url_raw("https://api.cloudflare.com/client/v4/zones?name={$this->api_token_domain}"),
                    $cf_headers
                );
            } else {
                $response = wp_remote_get(
                    esc_url_raw("https://api.cloudflare.com/client/v4/zones?page={$current_page}&per_page={$per_page}"),
                    $cf_headers
                );
            }

            if (is_wp_error($response)) {
                return false;
            }

            $response_body = wp_remote_retrieve_body($response);
            $json = json_decode($response_body, true);

            if ($json['success'] == false) {

                $error = array();

                foreach ($json['errors'] as $single_error) {
                    $error[] = "{$single_error['message']} (err code: {$single_error['code']})";
                }

                $error = implode(' - ', $error);

                return false;
            }

            if (isset($json['result_info']) && is_array($json['result_info'])) {

                if (isset($json['result_info']['total_pages']) && (int) $json['result_info']['total_pages'] > $current_page) {
                    $pagination = true;
                    $current_page++;
                } else {
                    $pagination = false;
                }
            } else {

                if ($pagination)
                    $pagination = false;
            }

            if (isset($json['result']) && is_array($json['result'])) {

                foreach ($json['result'] as $domain_data) {

                    if (!isset($domain_data['name']) || !isset($domain_data['id'])) {
                        $error = __('Unable to retrive zone id due to invalid response data', 'WPOven CloudShield');
                        return false;
                    }

                    $zone_id_list[$domain_data['name']] = $domain_data['id'];
                }
            }
        } while ($pagination);


        if (!count($zone_id_list)) {
            $error = __('Unable to find domains configured on Cloudflare', 'WPOven CloudShield');
            return false;
        }

        return $zone_id_list;
    }


    /**
     * Get cloudflare custom ruleset Id.
     */
    function get_custom_ruleset_id($zone_id)
    {
        $this->objects = $this->main_instance->get_objects();
        $api_url = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/rulesets";
        $cf_headers      = $this->get_api_headers();
        // Make the API request
        $response = wp_remote_get($api_url, $cf_headers,);

        // Check for a valid response
        if (is_wp_error($response)) {
            return 'Error: ' . $response->get_error_message();
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $ruleset_id = array();

        // Ensure the response contains results
        if (isset($data['success']) && $data['success'] && isset($data['result'])) {
            foreach ($data['result'] as $ruleset) {
                if (
                    isset($ruleset['phase'], $ruleset['source']) &&
                    $ruleset['phase'] === 'http_request_firewall_custom' &&
                    $ruleset['source'] === 'firewall_custom'
                ) {
                    $ruleset_id['http_request_firewall_custom'] = $ruleset['id'];
                }

                if (
                    isset($ruleset['phase'], $ruleset['source']) &&
                    $ruleset['phase'] === 'http_ratelimit' &&
                    $ruleset['source'] === 'rate_limit'
                ) {
                    $ruleset_id['http_ratelimit'] = $ruleset['id'];
                }
            }

            return $ruleset_id;
        }

        return null;
    }

    /**
     * Get all cloudflare WAF custom rules.
     */
    function get_waf_custom_rules($zone_id, $custom_ruleset_id)
    {
        $this->objects = $this->main_instance->get_objects();
        $cf_headers      = $this->get_api_headers();
        $api_url = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/rulesets/{$custom_ruleset_id}";
        // Make the GET request to fetch the ruleset details
        $response = wp_remote_get($api_url, $cf_headers);

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $rules = array();
        $rules_desc = array();
        $combined = array();
        if (isset($data['success']) && $data['success']) {
            // Check if rules exist and extract their IDs
            if (!empty($data['result']['rules'])) {

                foreach ($data['result']['rules'] as $rule) {
                    $rules[] = $rule['id'];
                    $rules_desc[] = $rule['description'];
                }
                $combined = array_combine($rules, $rules_desc);
            }

            return $combined; // Return an array of rule IDs

        }
        return 0;
    }

    /**
     * Cteate cloudflare login captcha custom rules.
     */
    function create_login_captcha_rule($zone_id, $custom_ruleset_id)
    {
        $cf_headers      = $this->get_api_headers();
        $expression = '(http.request.uri.path contains "/wp-login.php") or (http.request.uri.path eq "/wp-admin")';
        $api_url = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/rulesets/{$custom_ruleset_id}/rules";

        // Prepare the payload
        $payload = [
            'description' => 'Login Captcha Rule',
            'expression'  => $expression,
            'action'      => 'challenge',
        ];

        // Make the POST request
        $response = wp_remote_post($api_url, array_merge($cf_headers, [
            'body' => json_encode($payload),
        ]));

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['success']) && $data['success']) {
            $result['status'] = "ok";
            $result['message'] = "Login Captcha Rule created successfully.";
        } else {
            $result['status'] = "error";
            $result['message'] = $data;
        }

        return $result;
    }

    /**
     * Create all cloudflare WAF custom rules for rate limite.
     */
    function create_rate_limite_rules($zone_id, $custom_ratelimit_id)
    {
        $cf_headers      = $this->get_api_headers();
        $url = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/rulesets/{$custom_ratelimit_id}/rules";
        $expression = '(http.request.uri.path contains "/wp-login.php")';

        $rateLimitRule = [
            'description' => 'Wrong Login Rule',
            'expression' => $expression,
            'action' => 'block',
            'ratelimit' => [
                'characteristics' => [
                    'cf.colo.id',
                    'ip.src',
                ],
                'period' => 10,
                'requests_per_period' => 2,
                'mitigation_timeout' => 10
            ]
        ];

        // Make the POST request
        $response = wp_remote_post($url, array_merge($cf_headers, [
            'body' => json_encode($rateLimitRule),
        ]));
    }

    /**
     * Create IP block rule in cloudflare WAF custom rules.
     */
    function create_ip_block_rule($options, $zone_id, $custom_ruleset_id)
    {
        $result = array();
        $cf_headers = $this->get_api_headers();
        $expression_parts = [];

        // Block xmlrpc
        if ($options['cloudshield-cf-block-xmlrpc']) {
            $expression_parts[] = '(http.request.uri.path contains "/xmlrpc.php")';
        }

        // Block country for login
        if ($options['cloudshield-cf-country-block']) {
            $countries_list = array();
            $countries_list[] = $options['cloudshield-country-list'];
            $countries_string = implode('","', $countries_list);
            $countries_string = '"' . str_replace(',', '" "', $countries_string) . '"';
            if (!empty($countries_string)) {
                $expression_parts[] = '(http.request.uri.path eq "/wp-admin" and not ip.src.country in {' . $countries_string . '}) or (http.request.uri.path contains "/wp-login.php" and not ip.src.country in {' . $countries_string . '})';
            }
        }

        // Block ip
        if ($options['cloudshield-cf-ip-block']) {
            $ip_list = array();
            $ip_list[] = $options['cloudshield-ip-list'];
            $ip_string = implode(',', $ip_list);
            $ip_string = str_replace(',', ' ', $ip_string);
            if (!empty($ip_string)) {
                $expression_parts[] = '(ip.src in {' . $ip_string . '})';
            }
        }

        // Block non SEO
        if ($options['cloudshield-cf-block-non-seo']) {
            $expression_parts[] = '(cf.verified_bot_category eq "Search Engine Optimization")';
        }

        // Block AI crawler
        if ($options['cloudshield-cf-block-ai-crawlers']) {
            $expression_parts[] = '(cf.verified_bot_category eq "AI Crawler")';
        }

        $expression = implode(' or ', $expression_parts);
        $api_url = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/rulesets/{$custom_ruleset_id}/rules";

        // Prepare the payload
        $payload = [
            'description' => 'Static IP Block Rule',
            'expression'  => $expression,
            'action'      => 'block',
        ];

        // Make the POST request
        $response = wp_remote_post($api_url, array_merge($cf_headers, [
            'body' => json_encode($payload),
        ]));

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['success']) && $data['success']) {
            $result['status'] = "ok";
            $result['message'] = "IP Block Rule for login, DDoS & crawler protection is created successfully.";
        } else {
            $result['status'] = "error";
            $result['message'] = "Enable some of settings like XMLRPC, Country/IP Block, Login, Non-SEO, AI Crawlers, 404 Protection.";
        }

        return $result;
    }

    /**
     * Cteate cloudflare WAF custom rules.
     */
    function create_waf_custom_rule($options, $zone_id, $custom_ruleset_id)
    {
        $this->objects = $this->main_instance->get_objects();
        $return_array = array();

        if ($zone_id && $custom_ruleset_id) {
            $rules = $this->get_waf_custom_rules($zone_id, $custom_ruleset_id);
            $login_captcha_rule_exists = false;
            $ip_block_rule_exists = false;

            if (isset($rules) && !empty($rules)) {
                foreach ($rules as $rule_id => $rule_desc) {
                    if (in_array($rule_id, $rules) && $rule_desc === 'Login Captcha Rule') {
                        $login_captcha_rule_exists = true;
                    }

                    if (in_array($rule_id, $rules) && $rule_desc === 'IP Block Rule') {
                        $ip_block_rule_exists = true;
                    }
                }
            }

            if ($ip_block_rule_exists) {
                $return_array['status']['ip_block'] = 'ok';
                $return_array['success_msg']['ip_block'] = 'IP Block Rule already exists. Skipping creation.';
            } else {
                $result = $this->create_ip_block_rule($options, $zone_id, $custom_ruleset_id);
                $return_array['status']['ip_block'] = $result['status'];
                if ($result['status'] == 'ok') {
                    $return_array['success_msg']['ip_block'] = $result['message'];
                    $this->main_instance->set_single_config('cloudshield-cf-waf-enabled', 1);
                } else {
                    $return_array['error_msg']['ip_block'] = $result['message'];
                }
            }

            if ($login_captcha_rule_exists) {
                $return_array['status']['login_captcha'] = 'ok';
                $return_array['success_msg']['login_captcha'] = 'Login Captcha Rule already exists. Skipping creation.';
            } else {
                if ($options['cloudshield-cf-enable-captcha']) {
                    $result = $this->create_login_captcha_rule($zone_id, $custom_ruleset_id);
                    $return_array['status']['login_captcha'] = $result['status'];
                    if ($result['status'] == 'ok') {
                        $return_array['success_msg']['login_captcha'] = $result['message'];
                        $this->main_instance->set_single_config('cloudshield-cf-waf-enabled', 1);
                    } else {
                        $return_array['error_msg']['login_captcha'] = $result['message'];
                    }
                } else {
                    $return_array['status']['login_captcha'] = 'ok';
                    $return_array['success_msg']['login_captcha'] = 'Login Captcha Rule not enabled.';
                }
            }


            $this->main_instance->update_config();
        }

        die(wp_json_encode($return_array));
    }

    /**
     * Create Dynamic cloudflare WAF custom rules.
     */
    function create_dynamic_waf_custom_rule($results, $options, $zone_id, $custom_ruleset_id)
    {
        $cf_headers = $this->get_api_headers();
        $rules = $this->get_waf_custom_rules($zone_id, $custom_ruleset_id);
        if ($zone_id && $custom_ruleset_id) {
            $dynamic_ip_block_rule_exists = false;
            $dynamic_custom_rule_id = '';
            if (isset($rules) && $rules) {
                foreach ($rules as $rule_id => $rule_desc) {
                    if ($rule_desc === 'Dynamic IP Block Rule') {
                        $dynamic_custom_rule_id = $rule_id;
                        $dynamic_ip_block_rule_exists = true;
                    }
                }
            }

            if ($results) {
                $ip_string = implode(' ', $results); // Join the IPs with spaces
                if (!empty($ip_string)) {
                    $expression = '(ip.src in {' . $ip_string . '})'; // Construct the expression
                }
            }

            // Prepare the payload
            $payload = [
                'description' => 'Dynamic IP Block Rule',
                'expression'  => $expression,
                'action'      => 'block',
            ];

            if (!$dynamic_ip_block_rule_exists) {

                $api_url = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/rulesets/{$custom_ruleset_id}/rules";

                // Make the POST request
                $response = wp_remote_post($api_url, array_merge($cf_headers, [
                    'body' => json_encode($payload),
                ]));
            } else {
                $api_url = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/rulesets/{$custom_ruleset_id}/rules/{$dynamic_custom_rule_id}";

                // Make the POST request
                $response = wp_remote_request($api_url, array_merge($cf_headers, [
                    'method' => 'PATCH',
                    'body' => json_encode($payload),
                ]));
            }
        }
    }

    /**
     * Delete Dynamic cloudflare WAF custom rules.
     */
    function delete_dynamic_waf_custom_rule($zone_id, $custom_ruleset_id)
    {
        $result = array();
        $cf_headers = $this->get_api_headers(); // Replace this with your method for API headers
        $rule_ids = $this->get_waf_custom_rules($zone_id, $custom_ruleset_id);

        if ($zone_id && $custom_ruleset_id && is_array($rule_ids)) {
            foreach ($rule_ids as $rule_id => $rule_desc) {
                if ($rule_desc == "Dynamic IP Block Rule") {
                    $api_url = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/rulesets/{$custom_ruleset_id}/rules/{$rule_id}";

                    // Send DELETE request for each rule
                    $response = wp_remote_request($api_url, array_merge($cf_headers, [
                        'method' => 'DELETE',
                    ]));
                }
            }
        }

        die(wp_json_encode($result));
    }

    /**
     * Delete static cloudflare WAF custom rules.
     */
    function delete_waf_custom_rule($zone_id, $custom_ruleset_id)
    {
        $result = array();
        $cf_headers = $this->get_api_headers(); // Replace this with your method for API headers
        $rule_ids = $this->get_waf_custom_rules($zone_id, $custom_ruleset_id);

        if ($zone_id && $custom_ruleset_id && is_array($rule_ids)) {
            foreach ($rule_ids as $rule_id => $rule_desc) {
                $api_url = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/rulesets/{$custom_ruleset_id}/rules/{$rule_id}";

                // Send DELETE request for each rule
                $response = wp_remote_request($api_url, array_merge($cf_headers, [
                    'method' => 'DELETE',
                ]));

                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
            }

            if (isset($data['success']) && $data['success']) {
                $result['status'] = "ok";
                // $this->main_instance->set_single_config('cloudshield-cf-waf-enabled', 0);
                // $this->main_instance->update_config();
            } else {
                $result['status'] = "error";
            }

            $this->main_instance->set_single_config('cloudshield-cf-waf-enabled', 0);
            $this->main_instance->update_config();
        }

        die(wp_json_encode($result));
    }

    function create_wrong_login_custom_rule($blocked_ips, $options, $cloudshield_cf_zoneid, $custom_ruleset_id)
    {
        $cf_headers = $this->get_api_headers(); // Get Cloudflare API headers

        $rule_ids = $this->get_waf_custom_rules($cloudshield_cf_zoneid, $custom_ruleset_id);

        if ($cloudshield_cf_zoneid && $custom_ruleset_id && is_array($rule_ids)) {
            foreach ($rule_ids as $rule_id => $rule_desc) {
                if ($rule_desc == "Block IP faild login") {
                    $this->update_wrong_login_custom_rule($blocked_ips, $options, $cloudshield_cf_zoneid, $custom_ruleset_id);
                    return;
                }
            }
        }

        $api_url = "https://api.cloudflare.com/client/v4/zones/{$cloudshield_cf_zoneid}/rulesets/{$custom_ruleset_id}/rules";

        // Construct Cloudflare rule to block IPs
        $expression = "";

        if (!empty($blocked_ips) && is_array($blocked_ips)) {
            $cleaned_ips = array_filter(array_map('trim', $blocked_ips)); // Trim and remove empty values
            if (!empty($cleaned_ips)) {
                $ip_list = implode(' ', $cleaned_ips); // Join IPs with space
                $expression = '(http.request.uri.path contains "/wp-admin" and ip.src in {' . $ip_list . '}) or (http.request.uri.path contains "/wp-login.php" and ip.src in {' . $ip_list . '})';
            }
        }

        // Prepare the payload
        $payload = [
            'description' => 'Block IP faild login',
            'expression'  => $expression,
            'action'      => 'block',
        ];

        // Make the POST request
        $response = wp_remote_post($api_url, array_merge($cf_headers, [
            'body' => json_encode($payload),
        ]));


        return;
    }



    function update_wrong_login_custom_rule($blocked_ips, $options, $cloudshield_cf_zoneid, $custom_ruleset_id)
    {
        $result = array();
        $cf_headers = $this->get_api_headers(); // Replace this with your method for API headers
        $rule_ids = $this->get_waf_custom_rules($cloudshield_cf_zoneid, $custom_ruleset_id);

        if ($cloudshield_cf_zoneid && $custom_ruleset_id && is_array($rule_ids)) {
            foreach ($rule_ids as $rule_id => $rule_desc) {
                if ($rule_desc == "Block IP faild login") {
                    $api_url = "https://api.cloudflare.com/client/v4/zones/{$cloudshield_cf_zoneid}/rulesets/{$custom_ruleset_id}/rules/{$rule_id}";

                    // Construct Cloudflare rule to block IPs
                    $expression = "";

                    if (!empty($blocked_ips) && is_array($blocked_ips)) {
                        $cleaned_ips = array_filter(array_map('trim', $blocked_ips)); // Trim and remove empty values
                        if (!empty($cleaned_ips)) {
                            $ip_list = implode(' ', $cleaned_ips); // Join IPs with space
                            $expression = '(http.request.uri.path contains "/wp-admin" and ip.src in {' . $ip_list . '}) or (http.request.uri.path contains "wp-login.php" and ip.src in {' . $ip_list . '})';
                        }
                    }

                    // Prepare the payload
                    $payload = [
                        'description' => 'Block IP faild login',
                        'expression'  => $expression,
                        'action'      => 'block',
                    ];

                    // Send DELETE request for each rule
                    $response = wp_remote_request($api_url, array_merge($cf_headers, [
                        'method' => 'PATCH',
                        'body' => json_encode($payload)
                    ]));
                }
            }
        }

        return;
    }

    function delete_wrong_login_custom_rule($cloudshield_cf_zoneid, $custom_ruleset_id)
    {
        $result = array();
        $cf_headers = $this->get_api_headers(); // Replace this with your method for API headers
        $rule_ids = $this->get_waf_custom_rules($cloudshield_cf_zoneid, $custom_ruleset_id);

        if ($cloudshield_cf_zoneid && $custom_ruleset_id && is_array($rule_ids)) {
            foreach ($rule_ids as $rule_id => $rule_desc) {
                if ($rule_desc == "Block IP faild login") {
                    $api_url = "https://api.cloudflare.com/client/v4/zones/{$cloudshield_cf_zoneid}/rulesets/{$custom_ruleset_id}/rules/{$rule_id}";

                    // Send DELETE request for each rule
                    $response = wp_remote_request($api_url, array_merge($cf_headers, [
                        'method' => 'DELETE',
                    ]));
                }
            }
        }

        return;
    }

    /**
     * Check cloudflre WAF rules enable or not.
     */
    function is_waf_enabled()
    {
        $this->objects = $this->main_instance->get_objects();
        if ($this->main_instance->get_single_config('cloudshield-cf-waf-enabled', 0) > 0)
            return true;
        return false;
    }
}
