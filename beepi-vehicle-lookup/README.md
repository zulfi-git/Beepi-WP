# Beepi Vehicle Lookup Plugin

A WordPress plugin that provides vehicle information lookup functionality using Cloudflare Worker integration.

## Description

This plugin adds a vehicle lookup feature to your WordPress site, allowing users to search for vehicle information using registration numbers. The plugin integrates with a Cloudflare Worker to fetch vehicle data.

## Installation

1. Download the plugin zip file
2. Go to WordPress admin panel > Plugins > Add New
3. Click "Upload Plugin" and select the downloaded zip file
4. Click "Install Now" and then "Activate"

## Usage

1. Add the vehicle lookup form to any page or post using the shortcode:
```
[vehicle_search]
```

2. The form will appear, allowing users to enter vehicle registration numbers
3. Results will be displayed below the form after submission

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Active internet connection for Cloudflare Worker communication

## Features

- Clean, responsive interface
- AJAX-powered searches
- Bootstrap styling
- Error handling and user feedback
- Integration with Cloudflare Worker API

## Support

For support or feature requests, please visit [beepi.no](https://beepi.no) or create an issue in our repository.

## License

This plugin is licensed under the GPL v2 or later.
