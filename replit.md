# Beepi.no Vehicle Lookup System

## Overview

Beepi.no is a Norwegian vehicle information service that allows users to look up vehicle data by registration number. The system integrates with official Norwegian vehicle registration authorities (Statens vegvesen) to provide comprehensive vehicle information including ownership details, technical specifications, and registration history. The platform operates as a WordPress-based web application with custom plugins for vehicle lookup functionality and e-commerce integration through WooCommerce and Vipps payment processing.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### WordPress Foundation
The system is built on WordPress 6.8+ as the core content management system, providing the foundation for content delivery, user management, and plugin architecture. WordPress handles the public-facing website, administrative interface, and serves as the platform for custom functionality integration.

### Custom Vehicle Lookup Plugin (Beepi-WP)
A custom WordPress plugin provides the core vehicle lookup functionality. The plugin includes:
- **Vehicle Lookup Class**: Handles API communication with Norwegian vehicle registration services
- **Admin Interface**: WordPress admin pages for managing vehicle lookup settings and viewing search history
- **Rate Limiting**: Implements API rate limiting to manage external service costs
- **Caching System**: Stores vehicle lookup results to reduce API calls and improve performance
- **URL Routing**: Custom rewrite rules for clean URLs like `/sok/CU11262` for direct vehicle lookups

### E-commerce Integration
WooCommerce powers the payment system for premium features:
- **Product Management**: Digital products for vehicle owner information and detailed reports
- **Vipps Payment Gateway**: Integration with Norwegian mobile payment service for seamless checkout
- **Order Processing**: Automatic fulfillment of digital vehicle information products
- **SMS Notifications**: Order confirmation and delivery notifications via SMS

### Frontend User Interface
The frontend uses a modern responsive design with:
- **AJAX-powered Search**: Real-time vehicle lookup without page reloads
- **Dynamic URL Updates**: Clean URLs that update during search navigation
- **Tabbed Interface**: Organized display of vehicle information across multiple categories
- **Mobile-responsive Design**: Optimized for mobile and desktop viewing

### Data Storage and Caching
- **WordPress Database**: Stores user data, orders, and cached vehicle information
- **Transient API**: WordPress transients for temporary data caching
- **Session Management**: Handles user search sessions and state management

### Security and Performance
- **Rate Limiting**: Prevents API abuse and manages external service costs
- **Data Validation**: Input sanitization and validation for registration numbers
- **Error Handling**: Comprehensive error management with user-friendly messaging
- **Performance Optimization**: Caching strategies and optimized database queries

## External Dependencies

### Norwegian Vehicle Registration API
Primary data source for vehicle information, providing:
- Vehicle specifications and technical details
- Registration history and ownership changes
- EU control (inspection) dates and status
- Official vehicle identification data

### Vipps Payment Service
Norwegian mobile payment platform integration:
- Secure payment processing for premium features
- Express checkout functionality
- Order confirmation and tracking
- Refund and transaction management

### Twilio SMS Service
SMS notification system for order confirmations:
- International SMS delivery capabilities
- Delivery status tracking
- Phone number formatting and validation
- Error handling and retry mechanisms

### FluentMail
Email service integration for:
- Transactional email delivery
- Order confirmations and receipts
- Customer communication management
- Email template system

### WordPress Ecosystem
- **WooCommerce**: E-commerce functionality and payment processing
- **Rank Math**: SEO optimization and metadata management
- **LiteSpeed Cache**: Performance optimization and caching
- **Sabberworm CSS Parser**: CSS processing and optimization

### Third-party Services
- **Google Tag Manager**: Analytics and tracking implementation
- **Car Logo CDN**: External service for vehicle manufacturer logos
- **Font Services**: Web font delivery for typography
- **CDN Services**: Content delivery network for static assets