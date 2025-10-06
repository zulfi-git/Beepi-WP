# Overview

This is a WordPress-based vehicle lookup service for Norwegian vehicle registration data called "Beepi.no". The system allows users to search for vehicle information by registration number and purchase owner details through WooCommerce integration. The service integrates with Norwegian vehicle registration authorities (Statens Vegvesen) to provide comprehensive vehicle information including technical specifications, registration history, and owner data.

# Recent Changes (October 2025)

## App-Like Mobile Experience (Latest Update)
- **Card-Based UI**: Transformed entire interface into floating white cards on light gray background (#f5f5f9) for modern app feel
- **Subtle Shadows**: Replaced heavy borders with soft floating shadows (0 2px 8px rgba(0,0,0,0.06))
- **Progressive Disclosure**: AI summary section now uses collapsible `<details>` element with preview text (first 120 chars) - starts collapsed by default
- **Horizontal Scroll Carousel**: Market listings now scroll horizontally with:
  - Smooth swipe gestures and touch scrolling
  - Scroll-snap behavior for precise card alignment
  - Fixed card widths (280px mobile → 360px desktop)
  - Custom styled scrollbar
- **CRO Optimization**: Moved tier selection cards higher in layout - now appears immediately after AI summary for better conversion
- **Smooth Animations**: Added staggered animations throughout:
  - fadeIn, slideUp, and scaleIn keyframe animations
  - Sequential delays on sections (100ms increments)
  - Touch ripple effects on all buttons (CTA, purchase)
  - Enhanced hover states with micro-interactions
- **Transition System**: Introduced CSS variables for consistent timing (150ms fast, 250ms base, 350ms slow)
- **Better Spacing**: Applied 8px grid system with improved breathing room between cards (1rem gaps)
- **Touch Targets**: All interactive elements meet 44px minimum for mobile usability

## Unified Design System Implementation
- **Color System**: Consolidated from 12+ blue shades to one primary blue (#0ea5e9) with consistent hover states (#0284c7, #0369a1)
- **Typography Scale**: Established 6-tier font size system (xs: 0.75rem, sm: 0.875rem, base: 1rem, lg: 1.125rem, xl: 1.25rem, 2xl: 1.5rem)
- **Text Colors**: Reduced from 8+ gray variations to 3 semantic colors (primary: #1e293b, secondary: #64748b, muted: #94a3b8)
- **Font Family**: Added system font stack for consistent cross-platform typography
- **CSS Variables**: All colors, fonts, gradients, shadows, and transitions now use CSS custom properties for maintainability

## Mobile-First UI Enhancements
- Streamlined dashboard by removing average response time and system info sections
- Enhanced market listings with "Vis flere annonser på Finn.no" button using Finn.no search URLs
- Replaced brain emoji with prominent 50px OpenAI logo in AI summary section
- Removed unnecessary wrapper divs and padding for maximum mobile screen space utilization
- Fixed year badge positioning with CSS truncation for long vehicle titles

## Visual Cohesion Improvements
- All cards have consistent 12px border-radius and floating shadows
- All interactive elements (links, buttons) use unified primary blue with consistent hover effects
- Pricing tiers updated to use new blue scheme with subtle shadows
- All sections now follow the same border-radius, shadow, and spacing patterns

# User Preferences

Preferred communication style: Simple, everyday language.
Design preference: Mobile-first with maximum screen space utilization, minimal padding/margins, coherent typography and colors.

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