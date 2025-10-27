/**
 * Validate Norwegian registration number with minimal client-side rules
 * - Check for empty input
 * - Check for valid characters only (A-Z, ÆØÅ, 0-9)
 * - Check max length (7 characters after normalization)
 * 
 * Note: Norwegian license plates can use A-Z (including ÆØÅ for personalized plates) and digits 0-9.
 * Backend/worker handles deeper format verification.
 * 
 * @param {string} regNumber - The normalized registration number to validate
 * @returns {object} Validation result with 'valid' boolean and 'error' message
 */
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

// UMD (Universal Module Definition) export
(function (root, factory) {
    if (typeof module === 'object' && typeof module.exports === 'object') {
        module.exports = factory();
    } else if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else {
        root.validateRegistrationNumber = factory();
    }
})(typeof self !== 'undefined' ? self : this, function () {
    return validateRegistrationNumber;
});
