
/**
 * @param {callback} callback
 * @param {number} intervalLength
 */
function setIntervalImmediate(callback, interval) {
    function test() {
        if (callback() === false) {
            clearInterval(clear);
        }
    }
    var clear = setInterval(test, interval);
    test();
};