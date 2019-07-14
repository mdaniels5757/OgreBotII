function sortByKey(object) {
    var keys = Object.keys(object);

    keys.sort();

    keys.forEach(key => {
        var val = object[key];
        delete object[key];
        object[key] = val;
    });

    return object;
};