#!/bin/bash
# Enhanced integration test for plate normalization with Unicode whitespace
# This script tests that both PHP and JavaScript handle Unicode whitespace consistently

echo "=========================================================="
echo "Enhanced Plate Normalization Integration Test"
echo "Testing Unicode Whitespace Handling"
echo "=========================================================="
echo ""

# Test PHP normalization
echo "Testing PHP normalize_plate() with Unicode whitespace..."
php -r '
// Mock WordPress functions for standalone testing
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

$test_plates = [
    "AB12345",                // No space
    "ab12345",                // Lowercase
    "AB 12345",              // Regular space
    "ab 12 345",             // Multiple spaces
    "  AB12345  ",           // Leading/trailing spaces
    "AB\u{00A0}12345",       // Non-breaking space (U+00A0)
    "AB\u{2003}12345",       // Em space (U+2003)
    "AB\u{2009}12345",       // Thin space (U+2009)
    "AB\u{200B}12345",       // Zero-width space (U+200B)
    "AB\t12345",             // Tab
    "AB\n12345",             // Newline
    "AB\u{00A0}\u{2003}12345", // Mixed Unicode spaces
];

$results = [];
$all_normalized = [];

foreach ($test_plates as $plate) {
    $normalized = Vehicle_Lookup_Helpers::normalize_plate($plate);
    $results[$normalized] = isset($results[$normalized]) ? $results[$normalized] + 1 : 1;
    $all_normalized[] = $normalized;
}

echo "  Tested " . count($test_plates) . " different input formats\n";
echo "  Result: " . count($results) . " unique normalized value(s)\n";

foreach ($results as $normalized => $count) {
    echo "    \"$normalized\" (appeared $count times)\n";
}

// Check if all normalized to the same value
if (count($results) === 1 && isset($results["AB12345"])) {
    echo "  ✓ PHP normalization working correctly!\n\n";
    exit(0);
} else {
    echo "  ✗ PHP normalization failed!\n\n";
    exit(1);
}
'

PHP_EXIT=$?

# Capture PHP output for comparison
PHP_OUTPUT=$(php -r '
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

$test_plates = [
    "AB12345",
    "ab12345",
    "AB 12345",
    "ab 12 345",
    "  AB12345  ",
    "AB\u{00A0}12345",
    "AB\u{2003}12345",
    "AB\u{2009}12345",
    "AB\u{200B}12345",
    "AB\t12345",
    "AB\n12345",
    "AB\u{00A0}\u{2003}12345",
];

$all_normalized = [];
foreach ($test_plates as $plate) {
    $all_normalized[] = Vehicle_Lookup_Helpers::normalize_plate($plate);
}
echo json_encode($all_normalized);
')

echo "Testing JavaScript normalizePlate() with Unicode whitespace..."
node -e "
const normalizePlate = require('./assets/js/normalize-plate.js');

const testPlates = [
    'AB12345',                // No space
    'ab12345',                // Lowercase
    'AB 12345',              // Regular space
    'ab 12 345',             // Multiple spaces
    '  AB12345  ',           // Leading/trailing spaces
    'AB\u00A012345',         // Non-breaking space (U+00A0)
    'AB\u200312345',         // Em space (U+2003)
    'AB\u200912345',         // Thin space (U+2009)
    'AB\u200B12345',         // Zero-width space (U+200B)
    'AB\t12345',             // Tab
    'AB\n12345',             // Newline
    'AB\u00A0\u200312345',   // Mixed Unicode spaces
];

const results = {};
const allNormalized = [];

testPlates.forEach(plate => {
    const normalized = normalizePlate(plate);
    results[normalized] = (results[normalized] || 0) + 1;
    allNormalized.push(normalized);
});

console.log('  Tested ' + testPlates.length + ' different input formats');
console.log('  Result: ' + Object.keys(results).length + ' unique normalized value(s)');

Object.entries(results).forEach(([normalized, count]) => {
    console.log(\`    \\\"\${normalized}\\\" (appeared \${count} times)\`);
});

if (Object.keys(results).length === 1 && results['AB12345']) {
    console.log('  ✓ JavaScript normalization working correctly!\\n');
    process.exit(0);
} else {
    console.log('  ✗ JavaScript normalization failed!\\n');
    process.exit(1);
}
"

JS_EXIT=$?

# Capture JavaScript output for comparison
JS_OUTPUT=$(node -e "
const normalizePlate = require('./assets/js/normalize-plate.js');
const testPlates = [
    'AB12345',
    'ab12345',
    'AB 12345',
    'ab 12 345',
    '  AB12345  ',
    'AB\u00A012345',
    'AB\u200312345',
    'AB\u200912345',
    'AB\u200B12345',
    'AB\t12345',
    'AB\n12345',
    'AB\u00A0\u200312345',
];
const allNormalized = [];
testPlates.forEach(plate => {
    allNormalized.push(normalizePlate(plate));
});
console.log(JSON.stringify(allNormalized));
")

echo "=========================================================="
echo "Comparing PHP and JavaScript outputs..."
echo ""

if [ "$PHP_OUTPUT" = "$JS_OUTPUT" ]; then
    echo "  ✓ PHP and JavaScript produce identical results!"
    echo "    Both normalize all test cases to: AB12345"
else
    echo "  ✗ PHP and JavaScript outputs differ!"
    echo "    PHP output: $PHP_OUTPUT"
    echo "    JS output:  $JS_OUTPUT"
fi

echo ""
echo "=========================================================="
if [ $PHP_EXIT -eq 0 ] && [ $JS_EXIT -eq 0 ] && [ "$PHP_OUTPUT" = "$JS_OUTPUT" ]; then
    echo "✓ All tests passed!"
    echo "  - PHP normalization handles Unicode whitespace"
    echo "  - JavaScript normalization handles Unicode whitespace"
    echo "  - Both implementations produce identical results"
    echo "  - Backward compatibility maintained"
    echo "=========================================================="
    exit 0
else
    echo "✗ Some tests failed!"
    echo "=========================================================="
    exit 1
fi
