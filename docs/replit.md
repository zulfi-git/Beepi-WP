# Development Notes

WordPress plugin for Beepi.no - Norwegian vehicle lookup with WooCommerce integration for owner details purchasing.

## Recent Changes (October 2025)

## Unified Design System Implementation
- **Color System**: Consolidated from 12+ blue shades to one primary blue (#0ea5e9) with consistent hover states (#0284c7, #0369a1)
- **Typography Scale**: Established 6-tier font size system (xs: 0.75rem, sm: 0.875rem, base: 1rem, lg: 1.125rem, xl: 1.25rem, 2xl: 1.5rem)
- **Text Colors**: Reduced from 8+ gray variations to 3 semantic colors (primary: #1e293b, secondary: #64748b, muted: #94a3b8)
- **Font Family**: Added system font stack for consistent cross-platform typography
- **CSS Variables**: All colors, fonts, gradients, and shadows now use CSS custom properties for maintainability

## Mobile-First UI Enhancements
- Streamlined dashboard by removing average response time and system info sections
- Enhanced market listings with "Vis flere annonser på Finn.no" button using Finn.no search URLs
- Replaced brain emoji with prominent 50px OpenAI logo in AI summary section
- Removed unnecessary wrapper divs and padding for maximum mobile screen space utilization
- Fixed year badge positioning with CSS truncation for long vehicle titles

## Visual Cohesion Improvements
- AI summary section and market listings now share identical blue gradient styling (#0ea5e9)
- All interactive elements (links, buttons) use unified primary blue with consistent hover effects
- Pricing tiers updated to use new blue scheme (removed old #007cba, #00a8f0)
- All sections now follow the same border-radius, shadow, and spacing patterns

## System Architecture

### WordPress Plugin
Plugin extends WordPress with vehicle lookup capabilities using WordPress coding standards.

### Frontend
Norwegian license plate styled input with real-time validation for various registration formats.

### Data & Caching
Caching for vehicle lookups to reduce API calls. Logged cache hits, falls back to fresh API calls on misses.

### WooCommerce Integration
Purchase detailed vehicle owner info. Vipps integration for payment. Pricing: 5-29 kr basic, 69 kr comprehensive package.

### SMS Notifications
Twilio for order confirmations with Norwegian phone number formatting and delivery tracking.

### Error Handling
Debug logging throughout: lookups, SMS, payments, system errors.

## External Dependencies

- **Statens Vegvesen**: Norwegian vehicle registration data API
- **Vipps**: Norwegian mobile payment
- **Twilio**: SMS notifications
- **LiteSpeed Cache**: Performance optimization
- **WordPress/WooCommerce**: Core platform
