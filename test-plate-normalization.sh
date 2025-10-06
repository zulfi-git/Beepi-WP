#!/bin/bash
# Integration test for plate normalization
# This script demonstrates that plates with different formats produce the same normalized result

echo "==================================="
echo "Plate Normalization Integration Test"
echo "==================================="
echo ""

# Test PHP normalization
echo "Testing PHP normalize_plate()..."
php -r '
// Mock WordPress functions for standalone testing without loading WordPress
// This allows the test to run independently while ensuring class dependencies are available
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

$test_plates = ["AB12345", "ab12345", "AB 12345", "ab 12 345", "  AB12345  "];
$results = [];

foreach ($test_plates as $plate) {
    $normalized = Vehicle_Lookup_Helpers::normalize_plate($plate);
    $results[$normalized] = isset($results[$normalized]) ? $results[$normalized] + 1 : 1;
    echo "  Input: \"$plate\" => Output: \"$normalized\"\n";
}

echo "\nAll " . count($test_plates) . " different formats normalized to " . count($results) . " unique value(s):\n";
foreach ($results as $normalized => $count) {
    echo "  \"$normalized\" (appeared $count times)\n";
}

if (count($results) === 1 && isset($results["AB12345"])) {
    echo "✓ PHP normalization working correctly!\n";
    exit(0);
} else {
    echo "✗ PHP normalization failed!\n";
    exit(1);
}
'

PHP_EXIT=$?

echo ""
echo "Testing JavaScript normalizePlate()..."
node -e '
const { normalizePlate } = require("./assets/js/normalize-plate.js");

const testPlates = ["AB12345", "ab12345", "AB 12345", "ab 12 345", "  AB12345  "];
const results = {};

testPlates.forEach(plate => {
    const normalized = normalizePlate(plate);
    results[normalized] = (results[normalized] || 0) + 1;
    console.log(`  Input: "${plate}" => Output: "${normalized}"`);
});

console.log(`\nAll ${testPlates.length} different formats normalized to ${Object.keys(results).length} unique value(s):`);
Object.entries(results).forEach(([normalized, count]) => {
    console.log(`  "${normalized}" (appeared ${count} times)`);
});

if (Object.keys(results).length === 1 && results["AB12345"]) {
    console.log("✓ JavaScript normalization working correctly!");
    process.exit(0);
} else {
    console.log("✗ JavaScript normalization failed!");
    process.exit(1);
}
'

JS_EXIT=$?

echo ""
echo "==================================="
if [ $PHP_EXIT -eq 0 ] && [ $JS_EXIT -eq 0 ]; then
    echo "✓ All tests passed!"
    echo "==================================="
    exit 0
else
    echo "✗ Some tests failed!"
    echo "==================================="
    exit 1
fi
