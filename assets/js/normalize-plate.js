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

// Export for Node.js (CommonJS)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { normalizePlate };
}

// Export for browser (global scope)
if (typeof window !== 'undefined') {
    window.normalizePlate = normalizePlate;
}
