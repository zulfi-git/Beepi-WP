# Overview

This is a WordPress-based vehicle lookup service for Norwegian vehicle registration data called "Beepi.no". The system allows users to search for vehicle information by registration number and purchase owner details through WooCommerce integration. The service integrates with Norwegian vehicle registration authorities (Statens Vegvesen) to provide comprehensive vehicle information including technical specifications, registration history, and owner data.

# User Preferences

Preferred communication style: Simple, everyday language.

# System Architecture

## WordPress Plugin Architecture
The core system is built as a WordPress plugin called "Beepi-WP" that extends WordPress functionality with vehicle lookup capabilities. The plugin follows WordPress coding standards and hooks into the WordPress ecosystem for content management, user authentication, and e-commerce functionality.

## Frontend Implementation
The frontend uses a combination of WordPress pages and custom JavaScript for dynamic content loading. The vehicle search interface features a Norwegian license plate styled input field with real-time validation patterns for different Norwegian registration formats (standard plates, electric vehicle plates, diplomatic plates, etc.).

## Data Processing and Caching
The system implements a caching mechanism for vehicle lookup results to reduce API calls to external services. Cache hits are logged for performance monitoring, and the system falls back to fresh API calls when cache misses occur.

## WooCommerce Integration
E-commerce functionality is handled through WooCommerce, enabling users to purchase detailed vehicle owner information. The system integrates with Vipps (Norwegian mobile payment solution) for seamless payment processing, with pricing tiers for basic owner info (5-29 kr) and comprehensive owner packages (69 kr).

## SMS Notification System
The platform includes SMS notifications powered by Twilio for order confirmations and updates. The system handles phone number formatting for Norwegian mobile numbers and tracks SMS delivery status with comprehensive logging.

## Error Handling and Logging
Extensive debug logging is implemented throughout the system, tracking vehicle lookup operations, SMS notifications, payment processing, and general system errors. This enables effective monitoring and troubleshooting of the service.

# External Dependencies

## Vehicle Data API
Primary integration with Norwegian vehicle registration authorities (Statens Vegvesen) for accessing official vehicle registration data, technical specifications, and ownership information.

## Payment Processing
- **Vipps**: Norwegian mobile payment platform for processing customer payments
- **WooCommerce Payments**: Additional payment gateway integration for card transactions

## Messaging Services
- **Twilio**: SMS notification service for customer communication and order updates
- **FluentMail**: Email service integration for order confirmations and system notifications

## Infrastructure Services
- **LiteSpeed Cache**: Caching solution for improved performance
- **WordPress Core**: Content management system foundation
- **WooCommerce**: E-commerce platform for product sales and order management

## Development Tools
- **Debug Log Manager**: WordPress plugin for centralized error logging and debugging
- **Rank Math**: SEO optimization plugin for search engine visibility