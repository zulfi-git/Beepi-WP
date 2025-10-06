#!/bin/bash
# Demo script showing cache key consistency between PHP and JavaScript

echo "=========================================================="
echo "Cache Key Consistency Demo"
echo "=========================================================="
echo ""

echo "Scenario: User copies plate 'AB 12345' from HTML"
echo "The copied text contains a non-breaking space (U+00A0)"
echo ""

# Test with non-breaking space
PLATE_WITH_NBSP="AB 12345"  # This contains U+00A0

echo "Testing PHP cache key generation..."
PHP_KEY=$(php -r '
if (!function_exists("get_query_var")) {
    function get_query_var($var) { return ""; }
}
if (!function_exists("sanitize_text_field")) {
    function sanitize_text_field($str) { return trim(strip_tags($str)); }
}
if (!function_exists("esc_attr")) {
    function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES, "UTF-8"); }
}

require_once __DIR__ . "/includes/class-vehicle-lookup-helpers.php";

$plate = "AB\u{00A0}12345";  // Non-breaking space
$normalized = Vehicle_Lookup_Helpers::normalize_plate($plate);
echo $normalized;
')

echo "  Input: 'AB 12345' (with U+00A0)"
echo "  Normalized: '$PHP_KEY'"
echo ""

echo "Testing JavaScript cache key generation..."
JS_KEY=$(node -e "
const normalizePlate = require('./assets/js/normalize-plate.js');
const plate = 'AB\u00A012345';  // Non-breaking space
const normalized = normalizePlate(plate);
console.log(normalized);
")

echo "  Input: 'AB 12345' (with U+00A0)"
echo "  Normalized: '$JS_KEY'"
echo ""

echo "Testing manual input (regular space)..."
MANUAL_PHP=$(php -r '
if (!function_exists("get_query_var")) {
    function get_query_var($var) { return ""; }
}
if (!function_exists("sanitize_text_field")) {
    function sanitize_text_field($str) { return trim(strip_tags($str)); }
}
if (!function_exists("esc_attr")) {
    function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES, "UTF-8"); }
}

require_once __DIR__ . "/includes/class-vehicle-lookup-helpers.php";

$plate = "AB 12345";  // Regular space
$normalized = Vehicle_Lookup_Helpers::normalize_plate($plate);
echo $normalized;
')

echo "  Input: 'AB 12345' (with regular space U+0020)"
echo "  Normalized: '$MANUAL_PHP'"
echo ""

echo "=========================================================="
echo "Results:"
echo "=========================================================="
echo ""

if [ "$PHP_KEY" = "$JS_KEY" ] && [ "$PHP_KEY" = "$MANUAL_PHP" ] && [ "$PHP_KEY" = "AB12345" ]; then
    echo "✓ SUCCESS! All variations normalize to the same cache key"
    echo ""
    echo "  Copy-pasted (U+00A0): $PHP_KEY"
    echo "  Manual typed (U+0020): $MANUAL_PHP"
    echo "  JavaScript result:     $JS_KEY"
    echo ""
    echo "  All three produce: AB12345"
    echo ""
    echo "  Benefits:"
    echo "    ✓ Same cache key regardless of input method"
    echo "    ✓ No duplicate API calls"
    echo "    ✓ Consistent behavior across PHP and JavaScript"
    echo "    ✓ Better user experience"
    echo ""
    exit 0
else
    echo "✗ FAILED! Cache keys don't match"
    echo ""
    echo "  PHP with U+00A0: $PHP_KEY"
    echo "  JS with U+00A0:  $JS_KEY"
    echo "  Manual (U+0020): $MANUAL_PHP"
    echo ""
    exit 1
fi
