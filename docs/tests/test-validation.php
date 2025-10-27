<?php
/**
 * Norwegian Plate Validation Test Script (PHP)
 * Tests the PHP validation logic
 */

// Include the helper class
require_once __DIR__ . '/../../includes/class-vehicle-lookup-helpers.php';

// Test cases - simplified to test only basic rules, not format patterns
$testCases = [
    // Valid cases - any combination of A-Z and 0-9 up to 7 chars
    ['input' => 'AB12345', 'expected' => true, 'description' => '7 chars: 2 letters + 5 digits'],
    ['input' => 'CO11204', 'expected' => true, 'description' => '7 chars: standard example'],
    ['input' => 'XY1234', 'expected' => true, 'description' => '6 chars: 2 letters + 4 digits'],
    ['input' => 'EL12345', 'expected' => true, 'description' => '7 chars: electric vehicle format'],
    ['input' => 'A1B2C3D', 'expected' => true, 'description' => '7 chars: mixed letters and digits'],
    ['input' => 'ABC1234', 'expected' => true, 'description' => '7 chars: 3 letters + 4 digits'],
    ['input' => 'A', 'expected' => true, 'description' => '1 char: single letter'],
    ['input' => '1', 'expected' => true, 'description' => '1 char: single digit'],
    ['input' => 'A1', 'expected' => true, 'description' => '2 chars'],
    ['input' => 'ABCDEFG', 'expected' => true, 'description' => '7 chars: all letters'],
    ['input' => '1234567', 'expected' => true, 'description' => '7 chars: all digits'],
    
    // Invalid cases - empty
    ['input' => '', 'expected' => false, 'description' => 'Empty input', 'expectedError' => 'tomt'],
    ['input' => '   ', 'expected' => false, 'description' => 'Whitespace only', 'expectedError' => 'tomt'],
    
    // Invalid cases - too long
    ['input' => 'AB123456', 'expected' => false, 'description' => 'Too long (8 chars)', 'expectedError' => 'lengre'],
    ['input' => 'ABCD1234', 'expected' => false, 'description' => 'Too long (8 chars)', 'expectedError' => 'lengre'],
    
    // Invalid cases - invalid characters
    ['input' => 'AB-1234', 'expected' => false, 'description' => 'Contains hyphen', 'expectedError' => 'bokstaver'],
    ['input' => 'ÆØ1234', 'expected' => false, 'description' => 'Contains ÆØÅ', 'expectedError' => 'bokstaver'],
    ['input' => 'AB!234', 'expected' => false, 'description' => 'Contains special char', 'expectedError' => 'bokstaver']
];

// Run tests
echo "🧪 Running Norwegian Plate Validation Tests (PHP)\n\n";
echo str_repeat('=', 80) . "\n";

$passed = 0;
$failed = 0;
$failures = [];

foreach ($testCases as $index => $testCase) {
    $normalized = Vehicle_Lookup_Helpers::normalize_plate($testCase['input']);
    $result = Vehicle_Lookup_Helpers::validate_registration_number($normalized);
    $testPassed = $result['valid'] === $testCase['expected'];
    
    // For invalid cases, check if error message contains expected keyword
    $errorMatch = true;
    if ($testCase['expected'] === false && isset($testCase['expectedError'])) {
        $errorMatch = $result['error'] && stripos($result['error'], $testCase['expectedError']) !== false;
    }
    
    $testSuccess = $testPassed && $errorMatch;
    
    if ($testSuccess) {
        $passed++;
        echo "✅ Test " . ($index + 1) . ": {$testCase['description']}\n";
        echo "   Input: \"{$testCase['input']}\" → Normalized: \"{$normalized}\"\n";
        echo "   Result: " . ($result['valid'] ? 'VALID ✓' : "INVALID - {$result['error']}") . "\n";
    } else {
        $failed++;
        $failures[] = [
            'testCase' => $testCase,
            'normalized' => $normalized,
            'result' => $result,
            'errorMatch' => $errorMatch
        ];
        echo "❌ Test " . ($index + 1) . ": {$testCase['description']}\n";
        echo "   Input: \"{$testCase['input']}\" → Normalized: \"{$normalized}\"\n";
        echo "   Expected: " . ($testCase['expected'] ? 'VALID' : 'INVALID') . "\n";
        echo "   Got: " . ($result['valid'] ? 'VALID' : "INVALID - {$result['error']}") . "\n";
        if (!$errorMatch && isset($testCase['expectedError'])) {
            echo "   ⚠️  Error message mismatch! Expected to contain \"{$testCase['expectedError']}\"\n";
        }
    }
    echo "\n";
}

echo str_repeat('=', 80) . "\n";
echo "\n📊 Test Summary\n\n";
echo "Total Tests: " . count($testCases) . "\n";
echo "✅ Passed: {$passed}\n";
echo "❌ Failed: {$failed}\n";
echo "Success Rate: " . number_format(($passed / count($testCases)) * 100, 1) . "%\n";

if ($failed > 0) {
    echo "\n❌ FAILED TESTS:\n\n";
    foreach ($failures as $index => $failure) {
        echo ($index + 1) . ". {$failure['testCase']['description']}\n";
        echo "   Input: \"{$failure['testCase']['input']}\" → Normalized: \"{$failure['normalized']}\"\n";
        echo "   Expected: " . ($failure['testCase']['expected'] ? 'VALID' : 'INVALID') . "\n";
        echo "   Got: " . ($failure['result']['valid'] ? 'VALID' : "INVALID - {$failure['result']['error']}") . "\n";
        echo "\n";
    }
    exit(1);
} else {
    echo "\n✅ All tests passed!\n\n";
    exit(0);
}
