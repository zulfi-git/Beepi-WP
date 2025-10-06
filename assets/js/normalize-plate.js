/**
 * Normalize Norwegian registration plate
 * - Convert to uppercase
 * - Remove all spaces (including Unicode whitespace)
 * 
 * @param {string} plate - The registration plate to normalize
 * @returns {string} The normalized plate
 */
function normalizePlate(plate) {
    if (!plate) return '';
    // Remove all Unicode whitespace characters:
    // \s = ASCII whitespace
    // \u00A0 = non-breaking space
    // \u2000-\u200B = various Unicode spaces (em space, en space, thin space, zero-width space, etc.)
    // \uFEFF = zero-width no-break space (BOM)
    return plate.toString().replace(/[\s\u00A0\u2000-\u200B\uFEFF]+/g, '').toUpperCase();
}

// UMD (Universal Module Definition) export
(function (root, factory) {
    if (typeof module === 'object' && typeof module.exports === 'object') {
        module.exports = factory();
    } else if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else {
        root.normalizePlate = factory();
    }
})(typeof self !== 'undefined' ? self : this, function () {
    return normalizePlate;
});
