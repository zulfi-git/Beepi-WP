
# Tier Selection Boxes - Archived Feature

**Removed Date**: January 2025  
**Reason**: Feature redesign - replaced with action boxes (Se eier, Se skader, Se pant)

## Overview

This document preserves the tier selection functionality that allowed users to choose between "Enkel rapport" (Basic) and "Premium rapport" when viewing vehicle information.

## Location in Codebase

**File**: `includes/class-vehicle-lookup-shortcode.php`  
**Method**: `render_owner_section($product_id)`  
**Lines**: Approximately 73-166

## HTML Structure

```html
<div id="tier-selection">
    <h3>Velg rapporttype</h3>
    <div class="tier-comparison">
        <!-- Basic Tier -->
        <div class="tier-card basic-tier">
            <div class="tier-header">
                <h4>[Product Name from WooCommerce]</h4>
                <div class="tier-price">
                    [Price display with sale/regular price]
                </div>
            </div>
            <div class="tier-features">
                <div class="feature-item">✓ Nåværende eier</div>
                <div class="feature-item">✓ Alle tekniske detaljer</div>
                <div class="feature-item">✓ EU-kontroll status</div>
            </div>
            <div class="tier-purchase">
                [Vipps buy button shortcode]
            </div>
        </div>

        <!-- Premium Tier -->
        <div class="tier-card premium-tier recommended">
            <div class="tier-badge">Mest populær</div>
            <div class="tier-header">
                <h4>[Product Name from WooCommerce]</h4>
                [Savings display if on sale]
                <div class="tier-price">
                    [Price display with sale/regular price]
                </div>
            </div>
            <div class="tier-features">
                <div class="feature-item">✓ Alt fra Basic rapport</div>
                <div class="feature-item">✓ Komplett eierhistorikk</div>
                <div class="feature-item">✓ Skadehistorikk</div>
                <div class="feature-item">✓ Detaljert kjøretøyrapport</div>
                <div class="feature-item">✓ Import</div>
            </div>
            <div class="tier-purchase">
                [Vipps buy button shortcode]
            </div>
        </div>
    </div>
    [Trust indicators]
</div>
```

## PHP Code

```php
private function render_owner_section($product_id) {
    // Get products for both tiers
    $basic_product = wc_get_product(62);
    $premium_product = wc_get_product(739);

    $basic_price = $basic_product ? $basic_product->get_regular_price() : '39';
    $basic_sale = $basic_product ? $basic_product->get_sale_price() : null;

    $premium_price = $premium_product ? $premium_product->get_regular_price() : '89';
    $premium_sale = $premium_product ? $premium_product->get_sale_price() : null;

    ob_start();
    ?>
    <div class="owner-section">
        <div id="owner-info-container">
            <!-- Tier selection HTML here -->
            <div id="tier-selection">
                <h3>Velg rapporttype</h3>
                <div class="tier-comparison">
                    <!-- Basic Tier Card -->
                    <div class="tier-card basic-tier">
                        <div class="tier-header">
                            <h4><?php echo $basic_product ? esc_html($basic_product->get_name()) : 'Basic rapport'; ?></h4>
                            <div class="tier-price">
                                <?php if ($basic_sale): ?>
                                    <span class="regular-price"><?php echo esc_html($basic_price); ?> kr</span>
                                    <span class="sale-price"><?php echo esc_html($basic_sale); ?> kr</span>
                                <?php else: ?>
                                    <span class="price"><?php echo esc_html($basic_price); ?> kr</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="tier-features">
                            <div class="feature-item">✓ Nåværende eier</div>
                            <div class="feature-item">✓ Alle tekniske detaljer</div>
                            <div class="feature-item">✓ EU-kontroll status</div>
                        </div>
                        <div class="tier-purchase">
                            <?php echo do_shortcode("[woo_vipps_buy_now id=62 /]"); ?>
                        </div>
                    </div>

                    <!-- Premium Tier Card -->
                    <div class="tier-card premium-tier recommended">
                        <div class="tier-badge">Mest populær</div>
                        <div class="tier-header">
                            <h4><?php echo $premium_product ? esc_html($premium_product->get_name()) : 'Premium rapport'; ?></h4>
                            <?php
                            // Calculate percentage discount if there's a sale price
                            if ($premium_sale && $premium_sale < $premium_price): 
                                $discount_percentage = round((($premium_price - $premium_sale) / $premium_price) * 100);
                                ?>
                                <div class="savings-display">
                                    Spar <?php echo esc_html($discount_percentage); ?>% ved å kjøpe denne!
                                </div>
                            <?php endif; ?>
                            <div class="tier-price">
                                <?php if ($premium_sale): ?>
                                    <span class="regular-price"><?php echo esc_html($premium_price); ?> kr</span>
                                    <span class="sale-price"><?php echo esc_html($premium_sale); ?> kr</span>
                                <?php else: ?>
                                    <span class="price"><?php echo esc_html($premium_price); ?> kr</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="tier-features">
                            <div class="feature-item">✓ Alt fra Basic rapport</div>
                            <div class="feature-item">✓ Komplett eierhistorikk</div>
                            <div class="feature-item">✓ Skadehistorikk</div>
                            <div class="feature-item">✓ Detaljert kjøretøyrapport</div>
                            <div class="feature-item">✓ Import</div>
                        </div>
                        <div class="tier-purchase">
                            <?php 
                            $vipps_button = do_shortcode("[woo_vipps_buy_now id=739 /]");
                            echo $vipps_button;
                            ?>
                            <script>
                            window.premiumVippsBuyButton = <?php echo json_encode($vipps_button); ?>;
                            </script>
                        </div>
                    </div>
                </div>
                <?php echo $this->render_trust_indicators(); ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
```

## WooCommerce Integration

**Product IDs**:
- Basic Report: Product ID `62`
- Premium Report: Product ID `739`

**Vipps Integration**:
- Uses `[woo_vipps_buy_now id=XX /]` shortcode for purchase buttons
- Premium button stored in JavaScript variable `window.premiumVippsBuyButton`

## Features

### Basic Tier (Product ID: 62)
- Nåværende eier (Current owner)
- Alle tekniske detaljer (All technical details)
- EU-kontroll status (EU control status)

### Premium Tier (Product ID: 739)
- All features from Basic tier
- Komplett eierhistorikk (Complete owner history)
- Skadehistorikk (Damage history)
- Detaljert kjøretøyrapport (Detailed vehicle report)
- Import information

### Pricing Display
- Shows regular price and sale price if applicable
- Calculates and displays discount percentage for premium tier
- Format: "XX kr" (Norwegian currency)

## User Interface Elements

1. **Tier Badge**: "Mest populær" on premium tier
2. **Savings Display**: Shows percentage saved when on sale
3. **Feature Checkmarks**: Visual indicators (✓) for included features
4. **Trust Indicators**: Displayed below tier cards

## Styling Classes

- `.tier-selection` - Main container
- `.tier-comparison` - Grid container for tier cards
- `.tier-card` - Individual tier card
- `.basic-tier` - Basic tier specific styling
- `.premium-tier` - Premium tier specific styling
- `.recommended` - Badge for recommended tier
- `.tier-badge` - "Mest populær" badge
- `.tier-header` - Header section with title and price
- `.tier-price` - Price display container
- `.regular-price` - Regular price (strikethrough when on sale)
- `.sale-price` - Sale price display
- `.tier-features` - Features list container
- `.feature-item` - Individual feature item
- `.tier-purchase` - Purchase button container
- `.savings-display` - Discount percentage display

## Reusability Notes

To reuse this feature:

1. Copy the PHP code from this document
2. Ensure WooCommerce products exist with IDs 62 and 739
3. Install Vipps payment plugin with buy now shortcode support
4. Add the CSS classes listed above to your stylesheet
5. Include trust indicators rendering method

## Related Files

- `includes/class-vehicle-lookup-shortcode.php` - Main implementation
- `assets/css/results.css` - Styling (search for tier-related classes)
- `includes/class-vehicle-lookup-helpers.php` - Trust indicators helper

## Replacement Feature

This tier selection was replaced with action boxes:
- Se eier (View owner)
- Se skader (View damages)
- Se pant (View lien)

Each action box opens a popup for upselling specific features.
