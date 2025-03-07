# WPOven CloudShield

![PHP Check Status](https://github.com/baseapp/wpoven_cloudshield/actions/workflows/action.yml/badge.svg)

**Contributors:** [WPOven](https://www.wpoven.com/)  
**Requires at least:** 6.6.2  
**Tested up to:** 6.6.2  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** [http://www.gnu.org/licenses/gpl-2.0.html](http://www.gnu.org/licenses/gpl-2.0.html)

## Description

WPOven CloudShield is a robust security plugin designed to safeguard your WordPress website using Cloudflare's powerful capabilities. This plugin provides comprehensive protection against various threats, ensuring the safety and performance of your site.

## Key Features

1. **Cloudflare-Integrated Login Captcha:**
   Adds an intelligent login CAPTCHA to secure your admin login page and prevent unauthorized access attempts.

2. **IP Blocking:**
   Blocks IP addresses based on specific conditions, including:
      1. Multiple failed login attempts.
      2. Custom-defined IP blocks.
      3. Excessive request rates.

3. **Country Blocking:**
   Restrict access to your site from specific countries, giving you full control over regional access.

4. **XMLRPC Blocking:**
   Disables XMLRPC endpoints to prevent exploitation and brute-force attacks targeting this protocol.

5. **Crawler Management:**
   Blocks unwanted crawlers, such as non-SEO crawlers and AI bots, to ensure only legitimate traffic reaches your website.

6. **404 Protection:**
   Provides additional safeguards against 404 client errors, such as bad requests, to enhance server performance and resilience.

## Installation

1. **Download the Plugin:**

   - To get the latest version of WPOven CloudShield, you can either:
     - [Visit WPOven's website](https://www.wpoven.com/plugins/wpoven-cloudshield) to learn more about the plugin.
     - Download directly from the GitHub repository: [Download](https://github.com/baseapp/wpoven_cloudshield/releases/download/1.0.0/wpoven-cloudshield-2024-11-12.zip).

2. **Upload the Plugin:**

   - Log in to your WordPress admin dashboard.
   - Navigate to **Plugins > Add New**.
   - Click on the **Upload Plugin** button.
   - Choose the downloaded ZIP file and click **Install Now**.

3. **Activate the Plugin:**

   - After installation, click on the **Activate Plugin** link.

4. **Configure Plugin Settings:**

   - Once activated, go to **Cloudflare Settings > WPOven CloudShield** in the WordPress admin menu.
   - Configure the plugin settings as per your requirements.

5. **Usage:**

   - WPOven CloudShield ensures WordPress security by blocking unwanted access, managing IPs, controlling request rates, enabling country blocks, and protecting against bots and malicious requests.

6. **Regular Updates:**
   - Keep the plugin updated for the latest features and security improvements. You can update the plugin through the **Plugins** section in your WordPress admin dashboard.

## Screenshots

![Cloudflare Settings](https://github.com/baseapp/wpoven_cloudshield/blob/main/assets/screenshots/clouldshield_general_settings.png)
![WAF Settings](https://github.com/baseapp/wpoven_cloudshield/blob/main/assets/screenshots/waf_rule_settings.png)

## Frequently Asked Questions

### 1. What does WPOven CloudShield do?

WPOven CloudShield secures your WordPress site by integrating Cloudflare features like login CAPTCHA, IP blocking, country restrictions, bot management, and XMLRPC protection.

### 2.How does the plugin block unwanted crawlers?

The plugin identifies and blocks non-SEO and AI crawlers, ensuring only legitimate traffic interacts with your site, reducing server load and enhancing performance.

### 3. Can I block specific countries from accessing my website?

Yes, WPOven CloudShield allows you to restrict access from specific countries through its country-blocking feature.

### 4. Does the plugin protect against brute-force login attacks?

Absolutely. The plugin uses Cloudflare's CAPTCHA and IP blocking to prevent brute-force attacks and unauthorized access attempts effectively.

### 5. How is IP blocking managed?

IPs can be blocked based on failed login attempts, custom rules, high request rates, or other suspicious activity patterns.

### 6. Does WPOven CloudShield work with all WordPress setups?

Yes, the plugin is compatible with most WordPress setups and works seamlessly with Cloudflare to enhance your site's security.

## Changelog

### 1.0.0

### Initial Release
- Added integration with Cloudflare for enhanced security features.
- Implemented login CAPTCHA to prevent brute-force attacks.
- Enabled IP blocking based on failed logins, request rates, and custom rules.
- Introduced country-specific blocking for restricted access.
- Added functionality to block XMLRPC endpoints for added protection.
- Integrated crawler management to block non-SEO and AI bots.
- Provided 404 client error protection for improved server performance.
- User-friendly interface for managing security settings.

## Upgrade Notice

### 1.0.0

### Upgrade Notice

### 1.0.0
- Welcome to WPOven CloudShield!
- This is the initial release of the plugin, providing comprehensive security features, including Cloudflare integration, IP and country blocking, login       CAPTCHA, and crawler management. Install now to secure your WordPress site effectively.