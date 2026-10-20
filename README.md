# Privatus Analytics for Craft CMS

Adds the cookieless [Privatus Analytics](https://privatusanalytics.com) tracking script to your Craft CMS front end.

Privatus Analytics counts visits, sources, pages and conversions without cookies, local storage or fingerprinting, so analytics does not need a consent banner by itself. Check with your own counsel for your country.

The plugin only prints the browser script in the page head. It never sends events from your server and sets no cookies. You need a Privatus Analytics account to see reports. Sign up at https://privatusanalytics.com.

## Requirements

This plugin requires Craft CMS 5.0.0 or later and PHP 8.2 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project's control panel and search for "Privatus Analytics". Then press "Install".

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require privatus/craft-privatus-analytics

# tell Craft to install the plugin
./craft plugin/install privatus-analytics
```

## Setup

1. In the control panel, go to Settings, Plugins, Privatus Analytics.
2. Enter your site id (it starts with `pa_`) and save. Nothing is loaded until you do.
3. Optional: choose user groups that are not tracked, add excluded paths, turn on modules such as click maps or Core Web Vitals.

The script is added to the `<head>` of front-end pages that render a template. It is skipped in the control panel, in live preview and preview requests, in Ajax requests and in console commands.

## Settings

| Setting | Effect |
| --- | --- |
| Site ID | The `data-site` attribute. Required. Accepts an environment variable such as `$PRIVATUS_ANALYTICS_SITE_ID`. |
| Script host | The address that serves `/js/pa.js`. Defaults to `https://privatusstats.com`. Change it only if you host your own copy. |
| Do not track users who can access the control panel | On by default. Keeps your own visits out of your reports. |
| Do not track users in these groups | Logged-in users in a chosen group get no script. |
| Excluded paths | Comma-separated paths the script never tracks (`data-exclude`). |
| Single-page app mode, manual pageviews, modules, allowed domains, path masks, extra query parameters, canonical URL, hash routing | Map one to one to the script's `data-*` attributes. See the [tracker attributes](https://privatusanalytics.com/docs/tracker/attributes). |

### Config file

Every setting can also be set in `config/privatus-analytics.php`, which takes precedence over the control panel:

```php
<?php

return [
    'siteId' => '$PRIVATUS_ANALYTICS_SITE_ID',
    'modules' => ['engage', 'clicks'],
    'excludedUserGroups' => ['editors'],
];
```

## External service

After you save a site id, every front-end page loads `https://privatusstats.com/js/pa.js` in the visitor's browser. The script sends the page address (query parameters are removed except campaign parameters and any you allow), the referrer, the browser language, the screen width, the time zone name and event names to `https://privatusstats.com/api/event`. The visitor's IP address is read in memory to derive an approximate country and to count unique visits, and it is not stored.

* Terms of service: https://privatusanalytics.com/legal/terms
* Privacy policy: https://privatusanalytics.com/legal/privacy

## License

MIT. See `LICENSE.md`.
