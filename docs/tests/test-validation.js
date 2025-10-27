#!/usr/bin/env node

/**
 * Norwegian Plate Validation Test Script
 * Tests the validation logic independently
 */

// Normalize plate function (from normalize-plate.js)
function normalizePlate(plate) {
    if (!plate) return '';
    return plate.toString().replace(/[\s\u00A0\u2000-\u200B\uFEFF]+/g, '').toUpperCase();
}

// Validation function (from vehicle-lookup.js)
function validateRegistrationNumber(regNumber) {
    // Check if empty
    if (!regNumber || regNumber.trim() === '') {
        return {
            valid: false,
            error: 'Registreringsnummer kan ikke være tomt'
        };
    }

    // Check for invalid characters first (only A-Z and 0-9)
    // This catches any non-ASCII characters before length check
    const invalidChars = /[^A-Z0-9]/;
    if (invalidChars.test(regNumber)) {
        return {
            valid: false,
            error: 'Registreringsnummer kan kun inneholde norske bokstaver (A-Z) og tall (0-9)'
        };
    }

    // Check max length (7 characters) - safe to check now since we know only ASCII
    if (regNumber.length > 7) {
        return {
            valid: false,
            error: 'Registreringsnummer kan ikke være lengre enn 7 tegn'
        };
    }

    // Check against valid Norwegian plate formats
    const validFormats = [
        /^[A-Z]{2}\d{4,5}$/,           // Standard vehicles and others
        /^E[KLVBCDE]\d{5}$/,           // Electric vehicles
        /^CD\d{5}$/,                   // Diplomatic vehicles
        /^\d{5}$/,                     // Temporary tourist plates
        /^[A-Z]\d{3}$/,               // Antique vehicles
        /^[A-Z]{2}\d{3}$/             // Provisional plates
    ];
    
    const isValidFormat = validFormats.some(format => format.test(regNumber));
    
    if (!isValidFormat) {
        return {
            valid: false,
            error: 'Ugyldig registreringsnummer format'
        };
    }

    return {
        valid: true,
        error: null
    };
}

// Test cases
const testCases = [
    // Valid cases
    { input: 'AB12345', expected: true, description: 'Standard format (2 letters + 5 digits)' },
    { input: 'CO11204', expected: true, description: 'Standard format example' },
    { input: 'XY1234', expected: true, description: 'Standard format (2 letters + 4 digits)' },
    { input: 'co11204', expected: true, description: 'Lowercase input (should be normalized)' },
    { input: 'AB 12345', expected: true, description: 'Input with space (should be normalized)' },
    { input: 'EL12345', expected: true, description: 'Electric vehicle format' },
    { input: 'EK12345', expected: true, description: 'Electric vehicle format (EK)' },
    { input: 'CD12345', expected: true, description: 'Diplomatic format' },
    { input: '12345', expected: true, description: 'Temporary tourist format' },
    { input: 'A123', expected: true, description: 'Antique vehicle format' },
    { input: 'AB123', expected: true, description: 'Provisional format' },
    
    // Invalid cases - empty
    { input: '', expected: false, description: 'Empty input', expectedError: 'tomt' },
    { input: '   ', expected: false, description: 'Whitespace only', expectedError: 'tomt' },
    
    // Invalid cases - too long
    { input: 'AB123456', expected: false, description: 'Too long (8 chars)', expectedError: 'lengre' },
    { input: 'ABCD1234', expected: false, description: 'Too long (8 chars)', expectedError: 'lengre' },
    
    // Invalid cases - invalid characters
    { input: 'AB-1234', expected: false, description: 'Contains hyphen', expectedError: 'bokstaver' },
    { input: 'ÆØ1234', expected: false, description: 'Contains ÆØÅ', expectedError: 'bokstaver' },
    { input: 'AB!234', expected: false, description: 'Contains special char', expectedError: 'bokstaver' },
    
    // Invalid cases - wrong format
    { input: 'A1234', expected: false, description: 'Wrong format (1 letter + 4 digits)', expectedError: 'format' },
    { input: '1234', expected: false, description: 'Wrong format (4 digits)', expectedError: 'format' },
    { input: 'ABCD', expected: false, description: 'Letters only', expectedError: 'format' },
    { input: 'ABC123', expected: false, description: 'Wrong format (3 letters + 3 digits)', expectedError: 'format' }
];

// Run tests
console.log('🧪 Running Norwegian Plate Validation Tests\n');
console.log('='.repeat(80));

let passed = 0;
let failed = 0;
const failures = [];

testCases.forEach((testCase, index) => {
    const normalized = normalizePlate(testCase.input);
    const result = validateRegistrationNumber(normalized);
    const testPassed = result.valid === testCase.expected;
    
    // For invalid cases, check if error message contains expected keyword
    let errorMatch = true;
    if (testCase.expected === false && testCase.expectedError) {
        errorMatch = result.error && result.error.toLowerCase().includes(testCase.expectedError);
    }
    
    const testSuccess = testPassed && errorMatch;
    
    if (testSuccess) {
        passed++;
        console.log(`✅ Test ${index + 1}: ${testCase.description}`);
        console.log(`   Input: "${testCase.input}" → Normalized: "${normalized}"`);
        console.log(`   Result: ${result.valid ? 'VALID ✓' : `INVALID - ${result.error}`}`);
    } else {
        failed++;
        failures.push({
            testCase,
            normalized,
            result,
            errorMatch
        });
        console.log(`❌ Test ${index + 1}: ${testCase.description}`);
        console.log(`   Input: "${testCase.input}" → Normalized: "${normalized}"`);
        console.log(`   Expected: ${testCase.expected ? 'VALID' : 'INVALID'}`);
        console.log(`   Got: ${result.valid ? 'VALID' : `INVALID - ${result.error}`}`);
        if (!errorMatch) {
            console.log(`   ⚠️  Error message mismatch! Expected to contain "${testCase.expectedError}"`);
        }
    }
    console.log('');
});

console.log('='.repeat(80));
console.log('\n📊 Test Summary\n');
console.log(`Total Tests: ${testCases.length}`);
console.log(`✅ Passed: ${passed}`);
console.log(`❌ Failed: ${failed}`);
console.log(`Success Rate: ${((passed / testCases.length) * 100).toFixed(1)}%`);

if (failed > 0) {
    console.log('\n❌ FAILED TESTS:\n');
    failures.forEach((failure, index) => {
        console.log(`${index + 1}. ${failure.testCase.description}`);
        console.log(`   Input: "${failure.testCase.input}" → Normalized: "${failure.normalized}"`);
        console.log(`   Expected: ${failure.testCase.expected ? 'VALID' : 'INVALID'}`);
        console.log(`   Got: ${failure.result.valid ? 'VALID' : `INVALID - ${failure.result.error}`}`);
        console.log('');
    });
    process.exit(1);
} else {
    console.log('\n✅ All tests passed!\n');
    process.exit(0);
}
