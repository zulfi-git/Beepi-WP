# CSS Architecture

The vehicle lookup CSS has been split into modular files for better maintainability and reduced file size concerns.

## File Structure

### Core Files

1. **variables.css** (~2KB)
   - CSS custom properties (colors, fonts, shadows, gradients)
   - Design tokens used across all components
   - Must be loaded first

2. **forms.css** (~3.4KB)
   - Form inputs and plate input styling
   - Search buttons and form controls
   - Vehicle lookup container

3. **results.css** (~12KB)
   - Vehicle data display components
   - Tags (fuel types, gearbox types)
   - Info sections and accordion
   - Status indicators and EU controls
   - Data tables

4. **ai-summary.css** (~3KB)
   - AI summary section styling
   - Highlights and red flags
   - AI recommendation display

5. **market.css** (~5KB)
   - Market listings display
   - Listing cards and components
   - Finn.no integration styling

6. **responsive.css** (~16KB)
   - Media queries and responsive adjustments
   - Additional components (order confirmation, free info guide, tier selection)
   - Premium upsell styling
   - Logo and branding styles

## Load Order

Files are loaded in dependency order via WordPress `wp_enqueue_style()`:

1. `variables.css` (no dependencies)
2. `forms.css` (depends on variables)
3. `results.css` (depends on variables)
4. `ai-summary.css` (depends on variables)
5. `market.css` (depends on variables)
6. `responsive.css` (depends on variables, forms, results)

## Original File

The original monolithic `vehicle-lookup.css` (1,788 lines) has been preserved as `vehicle-lookup.css.bak` for reference.

## Benefits

- **Reduced cognitive load**: Each file focuses on a specific feature area
- **Better caching**: Browsers can cache individual modules
- **Easier maintenance**: Changes to AI summary don't require loading form styles
- **Clearer dependencies**: Explicit CSS dependencies via WordPress enqueue system
- **Debugging**: Easier to identify which file contains specific styles
