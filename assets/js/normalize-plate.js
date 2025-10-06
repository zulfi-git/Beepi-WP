/**
 * Normalize Norwegian registration plate
 * - Convert to uppercase
 * - Remove all spaces
 * 
 * @param {string} plate - The registration plate to normalize
 * @returns {string} The normalized plate
 */
function normalizePlate(plate) {
    if (!plate) return '';
    return plate.toString().replace(/\s+/g, '').toUpperCase();
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
