# Changelog

All notable changes, bug fixes, and improvements to the Beepi Vehicle Lookup plugin.

---

## [7.5.1] - 2025-10-27

### Added
- Enhanced frontend validation for Norwegian registration plates with specific error messages
- Max length validation (7 characters) for registration numbers
- Norwegian character validation (A-Z and 0-9 only) with user-friendly error messages
- Real-time input validation feedback for better user experience

### Changed
- Improved client-side validation in `assets/js/vehicle-lookup.js` with detailed error reporting
- Enhanced PHP validation in `includes/class-vehicle-lookup-helpers.php` for consistency
- Updated validation error messages to be more specific in Norwegian:
  - Empty input: "Registreringsnummer kan ikke være tomt"
  - Too long: "Registreringsnummer kan ikke være lengre enn 7 tegn"
  - Invalid characters: "Registreringsnummer kan kun inneholde norske bokstaver (A-Z) og tall (0-9)"
  - Invalid format: "Ugyldig registreringsnummer format"
- Validation now returns structured response with `valid` flag and `error` message

### Fixed
- Consistent validation between frontend and backend
- Inputs failing validation are now properly blocked before being sent to backend

---

## [7.5.0] - 2025-10-25

### Fixed
- Fixed Eierhistorikk (owner history) section displaying dummy/mock data instead of real data
- Removed fallback to dummy Norwegian owner names and addresses
- Added graceful error message when owner history data is unavailable from Statens vegvesen

### Changed
- Simplified owner history display logic to always show real data when available
- Removed access token check that was preventing real data from being displayed
- Improved user feedback when owner history data is not available

---

## [7.4.0] - 2025-10-22

### Changed
- Version bump to 7.4.0

### Documentation
- Consolidated SEO documentation into organized /docs/seo/ directory
- Converted HTML test documentation files to Markdown for GitHub readability (UI prototype HTML files were preserved)
- Removed outdated housekeeping log (HOUSEKEEPING_v7.0.9.md)
- Streamlined documentation for single-developer workflow
- Improved documentation navigation and organization

---

## [7.3.0] - 2025-10-17

### Changed
- Version bump

---

## [7.2.0] - 2025-10-17

### Changed
- Version bump

---

## [7.1.0] - 2025-10-17

### Changed
- Version bump

---

## [7.0.9] - 2025-10-17

### Changed
- Version bump for documentation housekeeping

### Documentation
- Removed outdated version-specific documentation (VERSION_7.0.8_README.md, api-update-summary.md)
- Consolidated duplicate fix documentation (removed summary/quickstart duplicates)
- Simplified docs/README.md for single-developer workflow
- Streamlined docs/fixes/README.md - removed redundant guides
- Updated all documentation to reflect current version 7.0.9
- HTML test files already converted to Markdown (completed in previous version)

---

## [7.0.8] - 2025

### Changed
- Updated to support revamped Cloudflare Worker API standard
- Updated error response handling to new flat structure format
- Enhanced AI summary polling to handle new response envelope with error object
- Enhanced market listings polling to handle new response envelope with error object
- Added comprehensive error code mapping for AI and market listing failures
- Improved correlation ID tracking across all API responses

### Documentation
- Updated WordPress Integration Guide with new API structure
- All endpoints now use consistent response envelope pattern
- Error responses now include `error`, `code`, `timestamp`, and `correlationId` at root level

---

## [7.0.7] - 2025

### Changed
- Version bump

---

## [7.0.6] - 2024

### Changed
- Version bump

---

## [7.0.5] - 2024

### Changed
- Version bump

---

## [7.0.4] - 2024

### Changed
- Version bump

---

## [7.0.3] - 2024

### Added
- Unified design system with CSS variables
- AI summary integration with OpenAI
- Market listings integration from Finn.no
- Mobile-first UI improvements
- Enhanced error handling with correlation IDs

### Documentation Reorganization - January 2025
- Created organized `docs/` directory structure
- Consolidated all documentation into logical categories:
  - `docs/architecture/` - System architecture and analysis
  - `docs/refactoring/` - Refactoring plans and completion reports
  - `docs/fixes/` - Bug fixes and improvements
  - `docs/investigations/` - Technical investigations and analyses
  - `docs/tests/` - Test files and demonstrations
- Created comprehensive documentation index at `docs/README.md`
- Updated all documentation links in README.md
- Moved investigation summaries from root to `docs/investigations/`
- Moved test HTML files from root to `docs/tests/`
- Root directory now contains only essential files (README.md, CHANGELOG.md)

### Documentation Housecleaning - January 2025
- Removed meta-documentation (ORGANIZATION_SUMMARY.md) not needed for solo project
- Simplified replit.md - removed verbose descriptions and third-party oriented language
- Converted investigation-visual-summary.html to Markdown (FIRST_SEARCH_VISUAL_SUMMARY.md)
- Removed "team" and "stakeholder" language from documentation
- Streamlined navigation sections in docs/README.md
- Made documentation more concise and focused for single developer use

---

## Recent Fixes (2024)

### CSS Modularization
**Status**: ✅ Completed  
**Documentation**: [docs/refactoring/CSS_MODULARIZATION_SUMMARY.md](./docs/refactoring/CSS_MODULARIZATION_SUMMARY.md)

Split monolithic CSS file (1,788 lines) into 6 focused modules:
- `variables.css` - Design tokens
- `forms.css` - Form inputs and controls
- `results.css` - Vehicle data display
- `ai-summary.css` - AI summary section
- `market.css` - Market listings
- `responsive.css` - Media queries and responsive design

**Benefits**: Better maintainability, improved caching, reduced cognitive load

---

### Cache Removal
**Status**: ✅ Completed  
**Documentation**: [docs/fixes/CACHE_REMOVAL_SUMMARY.md](./docs/fixes/CACHE_REMOVAL_SUMMARY.md)

Removed WordPress transient caching from the plugin. Cloudflare KV now handles all caching at the edge.

**Benefits**: Simplified architecture, eliminated stale data issues, better performance

---

### AI Summary 404 Fix
**Status**: ✅ Completed  
**Documentation**: [ai-summary-404-fix.md](./docs/fixes/ai-summary-404-fix.md)

HTTP 404 responses during AI generation were treated as errors. Fixed to properly handle 404 as "generating" status.

**Impact**: AI summaries now display reliably for all users.

---

### Polling Conflict Fix
**Status**: ✅ Completed  
**Documentation**: [POLLING_CONFLICT_FIX.md](./docs/fixes/POLLING_CONFLICT_FIX.md)

Multiple polling instances running simultaneously caused race conditions in sequential lookups.

**Solution**: Proper polling lifecycle management with state tracking and cancellation.

**Impact**: Consistent, reliable display for all sequential lookups.

---

### Console Logging Enhancement
**Status**: ✅ Completed  
**Documentation**: [CONSOLE_LOGGING_FIX.md](./docs/fixes/CONSOLE_LOGGING_FIX.md)

Added comprehensive console logging (45+ statements) throughout lookup flow with visual indicators and phase markers.

**Impact**: Easier debugging and development.

---

### Second Viewing Fix
**Status**: ✅ Completed  
**Documentation**: [SECOND_VIEWING_FIX.md](./docs/fixes/SECOND_VIEWING_FIX.md)

Fixed UI clearing and data display inconsistencies in second vehicle lookups.

**Impact**: Reliable second viewing experience.

---

### Selector Fix
**Status**: ✅ Completed  
**Documentation**: [SELECTOR_FIX_DOCUMENTATION.md](./docs/fixes/SELECTOR_FIX_DOCUMENTATION.md)

Fixed DOM selectors to properly target and update listing elements.

**Impact**: Consistent listings display for all lookups.

---

## Refactoring Progress

### Phase 1: Quick Wins
**Status**: ✅ Completed  
**Documentation**: 
- [Plan](./docs/refactoring/REFACTOR_PHASE_1.md)
- [Completion](./docs/refactoring/PHASE_1_COMPLETION.md)

Fixed critical performance issues:
- Removed duplicate rate limit registration
- Fixed rewrite rules performance issue
- Simplified database initialization

### Phase 2: Admin Class Split
**Status**: ✅ Completed  
**Documentation**: 
- [Plan](./docs/refactoring/REFACTOR_PHASE_2.md)
- [Completion](./docs/refactoring/PHASE_2_COMPLETION.md)

Split monolithic admin class (1,197 lines) into focused components.

### Phase 3 & 4: Pending
**Documentation**: 
- [Phase 3 Plan](./docs/refactoring/REFACTOR_PHASE_3.md) - Live metrics
- [Phase 4 Plan](./docs/refactoring/REFACTOR_PHASE_4.md) - Testing

---

## Documentation Structure

As of January 2025, all documentation has been organized into:

```
docs/
├── README.md                 # Documentation index
├── architecture/             # System architecture & analysis
├── refactoring/              # Refactoring plans & completions
└── fixes/                    # Bug fixes & improvements
```

See [docs/README.md](./docs/README.md) for complete documentation navigation.

---

## Format

This changelog follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) conventions.

### Change Types
- **Added** - New features
- **Changed** - Changes to existing functionality
- **Deprecated** - Soon-to-be removed features
- **Removed** - Removed features
- **Fixed** - Bug fixes
- **Security** - Security fixes

---

**Maintainer**: Beepi.no  
**Last Updated**: October 2025
