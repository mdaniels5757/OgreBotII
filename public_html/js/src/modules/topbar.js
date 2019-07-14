/**
 * @param {Object} options
 */
function topBar(options) {
    function setHeight(height, div) {
        $(div).css("top", `${height * 26}px`);
    }

    if (typeof options !== "object") {
        options = { message: options };
    }

    var existing = $(".topbar:last");
    var div = $("<div />", { class: "topbar", text: options.message }).css(options.css || {});

    setTimeout(() => {
        div.remove();

        $(".topbar").each(setHeight);
    }, options.delay || 5000);

    if (existing[0]) {
        existing.after(div);
    } else {
        $("body").prepend(div);
    }

    setHeight($(".topbar").length - 1, div);
}