# Vehicle Information Lookup Plugin

A WordPress plugin that enables seamless vehicle information lookup through integration with BeepiWorker Cloudflare Worker.

## Description

This plugin provides a simple and efficient way to look up vehicle information using VIN numbers. It connects to the BeepiWorker Cloudflare Worker (https://github.com/zulfi-git/BeepiWorker) to fetch vehicle details.

## Features

- Easy-to-use shortcode `[vehicle_lookup]` for embedding the lookup form
- Responsive design that works on all devices
- Real-time VIN validation
- Clear display of vehicle information
- AJAX-powered lookups without page reload

## Installation

1. Upload the `vehicle-lookup-plugin` directory to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place the shortcode `[vehicle_lookup]` in any page or post where you want the lookup form to appear

## Usage

1. After installation, add the `[vehicle_lookup]` shortcode to any page or post
2. Enter a valid 17-character VIN in the form
3. Click "Look Up Vehicle" to see the vehicle information

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher

## Support

For issues and feature requests, please visit the [GitHub repository](https://github.com/zulfi-git/BeepiWorker).
