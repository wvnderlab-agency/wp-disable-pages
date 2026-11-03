# wp-disable-pages

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php)
![WordPress](https://img.shields.io/badge/WordPress-MU--Plugin-21759B?logo=wordpress)
[![WP Coding Standards](https://github.com/wvnderlab-agency/wp-disable-pages/actions/workflows/wp-coding-standards.yml/badge.svg)](https://github.com/wvnderlab-agency/wp-disable-pages/actions/workflows/wp-coding-standards.yml)

- [Installation](#installation)
- [Usage](#usage)
- [Development](#development)

## Installation

### Via Composer

```shell
composer require wvnderlab-agency/wp-disable-pages
```

### Via FTP

1. Download the repository zip file.
2. Unzip the file.
3. Upload the unzipped folder to the `/wp-content/muplugins` or `/wp-content/plugins/` directory on your server.
4. Navigate to the 'Plugins' section in your WordPress admin dashboard.
5. Find 'Disable posts' in the list and click 'Activate'.

### Via WordPress Admin Dashboard

1. Download the repository zip file.
2. Navigate to the 'Plugins' section in your WordPress admin dashboard.
3. Click 'Add New' and then 'Upload Plugin'.
4. Choose the downloaded zip file and click 'Install Now'.
5. After installation, click 'Activate Plugin'.

## Usage

### Filter Hooks

#### wvnderlab/disable-pages/enabled *(Default: true)*

This filter allows you to disable the plugin functionality.

```php
// disable the plugin functionality
add_filter( 'wvnderlab/disable-pages/enabled', '__return_false' );
```

#### wvnderlab/disable-pages/status-code *(Default: 404)*

This filter allows you to set the HTTP status code returned when posts are disabled.

You can set it to standard codes like 404 (Not Found), 410 (Gone), or redirect codes like 301 (Moved Permanently) or 302 (Found).

```php
// set the status code to 301 - Moved Permanently
add_filter( 'wvnderlab/disable-pages/status-code', fn() => 301 );
// set the status code to 302 - Found
add_filter( 'wvnderlab/disable-pages/status-code', fn() => 302 );
// set the status code to 307 - Temporary Redirect
add_filter( 'wvnderlab/disable-pages/status-code', fn() => 307 );
// set the status code to 308 - Permanent Redirect
add_filter( 'wvnderlab/disable-pages/status-code', fn() => 308 );
// set the status code to 410 - Gone
add_filter( 'wvnderlab/disable-pages/status-code', fn() => 410 );
```

#### wvnderlab/disable-pages/redirect-url *(Default: home_url())*

This filter allows you to override the redirect URL when posts are disabled and the `wvnderlab/disable-pages/status-code` filter is set to use a redirect status code (e.g., 301 or 302).

```php
// override the redirect URL
add_filter( 'wvnderlab/disable-pages/redirect_url', 'YOUR_REDIRECT_URL' );
```

## Development

### Install Dependencies

```shell
composer install
```

### Analyse Code-Quality with WP-Coding-Standards

```shell
composer analyze
```

### Refactor Code along WP-Coding-Standards

```shell
composer refactor
```
