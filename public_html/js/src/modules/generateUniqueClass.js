/**
 * @param {String} prefix
 * @return {String}
 */
function generateUniqueClass(prefix) {
    var now = Date.now();
    var uniqueNowClass;

    do {
        uniqueNowClass = `${prefix}-${now++}`;
    } while ($(`.${uniqueNowClass}`)[0]);

    return uniqueNowClass;
}
