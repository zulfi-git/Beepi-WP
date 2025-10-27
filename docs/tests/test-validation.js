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

// Validation function (simplified - matches vehicle-lookup.js)
function validateRegistrationNumber(regNumber) {
    // Check if empty
    if (!regNumber || regNumber.trim() === '') {
        return {
            valid: false,
            error: 'Registreringsnummer kan ikke være tomt'
        };
    }

    // Check for invalid characters (only A-Z, ÆØÅ and digits 0-9)
    // Personalized Norwegian plates can contain ÆØÅ (e.g., "LØØL")
    const invalidChars = /[^A-ZÆØÅ0-9]/;
    if (invalidChars.test(regNumber)) {
        return {
            valid: false,
            error: 'Registreringsnummer kan kun inneholde norske bokstaver (A-Z, ÆØÅ) og tall (0-9)'
        };
    }

    // Check max length (7 characters)
    if (regNumber.length > 7) {
        return {
            valid: false,
            error: 'Registreringsnummer kan ikke være lengre enn 7 tegn'
        };
    }

    // All basic checks passed - backend will verify format
    return {
        valid: true,
        error: null
    };
}

// Test cases - simplified to test only basic rules, not format patterns
const testCases = [
    // Valid cases - any combination of A-Z, ÆØÅ and 0-9 up to 7 chars
    { input: 'AB12345', expected: true, description: '7 chars: 2 letters + 5 digits' },
    { input: 'CO11204', expected: true, description: '7 chars: standard example' },
    { input: 'XY1234', expected: true, description: '6 chars: 2 letters + 4 digits' },
    { input: 'co11204', expected: true, description: 'Lowercase (normalized to uppercase)' },
    { input: 'AB 12345', expected: true, description: 'With space (normalized by removing space)' },
    { input: 'EL12345', expected: true, description: '7 chars: electric vehicle format' },
    { input: 'A1B2C3D', expected: true, description: '7 chars: mixed letters and digits' },
    { input: 'ABC1234', expected: true, description: '7 chars: 3 letters + 4 digits' },
    { input: 'A', expected: true, description: '1 char: single letter' },
    { input: '1', expected: true, description: '1 char: single digit' },
    { input: 'A1', expected: true, description: '2 chars' },
    { input: 'ABCDEFG', expected: true, description: '7 chars: all letters' },
    { input: '1234567', expected: true, description: '7 chars: all digits' },
    { input: 'LØØL', expected: true, description: 'Personalized plate with ÆØÅ' },
    { input: 'løøl', expected: true, description: 'Personalized plate lowercase (normalized)' },
    { input: 'ÆØÅ1234', expected: true, description: 'Plate with ÆØÅ and digits' },
    
    // Invalid cases - empty
    { input: '', expected: false, description: 'Empty input', expectedError: 'tomt' },
    { input: '   ', expected: false, description: 'Whitespace only', expectedError: 'tomt' },
    
    // Invalid cases - too long
    { input: 'AB123456', expected: false, description: 'Too long (8 chars)', expectedError: 'lengre' },
    { input: 'ABCD1234', expected: false, description: 'Too long (8 chars)', expectedError: 'lengre' },
    
    // Invalid cases - invalid characters
    { input: 'AB-1234', expected: false, description: 'Contains hyphen', expectedError: 'bokstaver' },
    { input: 'AB!234', expected: false, description: 'Contains special char', expectedError: 'bokstaver' }
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
