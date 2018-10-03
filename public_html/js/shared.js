/**
 * @constant {Symbol}
 */
const tooltipContent = "tooltip-content";

function lcfirst(string) {
    return string.charAt(0).toLowerCase() + string.slice(1);
}

/**
 * @param {String} prefix
 * @return {String}
 */
function generateUniqueClass(prefix) {
    var now = Date.now().toString();
    var uniqueNowClass;

    do {
        uniqueNowClass = `${prefix}-${now++}`;
    } while ($(`.${uniqueNowClass}`)[0]);

    return uniqueNowClass;
}

Array.prototype._flatten = function() {
    return this.reduce((a, b) => a.concat(b), []);
};

window.MDialog = class MDialog {
    /**
     * @param {JQuery} jq
     * @param {Object} options
     */
    constructor(jq, options) {
        /**
         * @type {JQuery}
         */
        this.dialog = $(jq).dialog(
            $.extend({}, { autoOpen: false, modal: true, width: 600 }, options)
        );
    }

    /**
     * @param {JQuery} jq
     */
    addOpenButton(jq) {
        $(jq).click(this.open.bind(this));
    }

    /**
     * @param {JQuery} jq
     */
    addCloseButton(jq) {
        $(jq).click(this.close.bind(this));
    }

    open() {
        this.dialog.dialog("open");
    }

    close() {
        this.dialog.dialog("close");
    }
};

window.XDomain = class XDomain {
    constructor() {
        /**
         * @private
         */
        this.listeners = [];

        /**
         * @private
         */
        this._promise = new Promise((resolve, reject) => {
            window.setIntervalImmediate(
                () => {
                    var input = $("input[data-xd-auto]");

                    if (input[0]) {
                        let data = $(input).data();

                        var { xdDomain, xdPath, xdTimeout } = data;

                        function stopListeningForPing() {
                            $window.off("message", null, checkedResolved);
                            if (failTimeout) {
                                window.clearTimeout(failTimeout);
                            }
                        }

                        function checkOrigin(originalEvent) {
                            return originalEvent.origin === xdDomain;
                        }

                        const checkedResolved = e => {
                            var originalEvent = e.originalEvent;

                            if (!checkOrigin(originalEvent)) {
                                return;
                            }

                            if (originalEvent.data === "ping") {
                                resolve();
                                stopListeningForPing();
                                $window.on("message", e => {
                                    var originalEvent = e.originalEvent;

                                    if (!checkOrigin(originalEvent)) {
                                        return;
                                    }

                                    this.listeners.forEach(listener => {
                                        listener(originalEvent.data);
                                    });
                                });
                            }
                        };

                        var $window = $(window);
                        var failTimeout = xdTimeout && window.setTimeout(
                                () => {
                                    reject();
                                    stopListeningForPing();
                                },
                                xdTimeout
                            );

                        $window.on("message", checkedResolved);
                        /**
                         * @private
                         */
                        this.iframe = $("<iframe/>")
                            .attr({ style: "display: none", src: `${xdDomain}/${xdPath}` })
                            .appendTo("body");

                        /**
                         * @private
                         */
                        this.origin = xdDomain;

                        return false;
                    }

                    let ready = $.isReady;
                    if (ready) {
                        reject();
                    }

                    return !ready;
                },
                100
            );
        });
    }

    /**
     * @callback listener
     */
    addListener(listener) {
        this.listeners.push(listener);
    }

    /**
     * @param mixed message
     */
    postMessage(message) {
        this.iframe[0].contentWindow.postMessage(message, this.origin);
    }

    /**
     * @return {Promise}
     */
    ready() {
        return this._promise;
    }
};

/**
 * @param {callback} callback
 * @param {number} intervalLength
 */
window.setIntervalImmediate = function(callback, interval) {
    function test() {
        if (callback() === false) {
            window.clearInterval(clear);
        }
    }
    var clear = window.setInterval(test, interval);
    test();
};

window.sortByKey = object => {
    var keys = Object.keys(object);

    keys.sort();

    keys.forEach(key => {
        var val = object[key];
        delete object[key];
        object[key] = val;
    });

    return object;
};

/*******************
 * spinner
 *******************/
{
    class Spinner {
        constructor(jq, options) {
            let webRoot = $("#web-root").val() || ".";

            this._jq = jq;
            this._div = $("<div class='spinner-div'/>")
                .hide()
                .append("<div/>")
                .append($(`<img src='${webRoot}/images/ajax-loader.gif'/>`))
                .appendTo("body")
                .show(options);
            this.redraw();
        }

        redraw() {
            var coords = this._jq.map(function() {
                const $this = $(this);
                var position = $this.offset() || { top: 0, left: 0 };

                return $.extend(position, {
                    bottom: position.top + $this.outerHeight(true),
                    right: position.left + $this.outerWidth()
                });
            });
            var [ top, left, bottom, right ] = [
                [ "top" ],
                [ "left" ],
                [ "bottom", 1 ],
                [ "right", 1 ]
            ].map(
                ([ index, max ]) =>
                    Math[max ? "max" : "min"].apply(window, $.map(coords, coords => coords[index]))
            );
            this._div.css({
                width: `${right - left}px`,
                height: `${bottom - top}px`,
                top: `${top}px`,
                left: `${left}px`
            });
        }

        async remove(options) {
            await this._div.hide(options).promise();
            this._div.remove();
        }
    }

    class SpinnerFactory {
        constructor() {
            this.store = [];
        }

        create(jq, options) {
            this.store.push(new Spinner(jq, options));
        }

        redraw(filterJq) {
            this.get(filterJq).forEach(spinner => {
                spinner.redraw.call(spinner);
            });
        }

        remove(filterJq, options) {
            this.get(filterJq).forEach(spinner => {
                this.store = this.store.filter(removeSpinner => spinner !== removeSpinner);
                spinner.remove.call(spinner, options);
            });
        }

        get(filterJq = undefined) {
            var all = this.store;
            if (filterJq) {
                all = all.filter(
                    spinner => !spinner._jq.not(filterJq)[0] && !$(filterJq).not(spinner._jq)[0]
                );
            }
            return all;
        }
    }

    let spinnerFactory = new SpinnerFactory();

    //static
    $.spinner = { all: spinnerFactory };

    $.fn.spinner = function(options) {
        if (this[0]) {
            if ("redraw" === options) {
                spinnerFactory.redraw(this);
            } else if ("remove" === options) {
                spinnerFactory.remove(this);
            } else {
                spinnerFactory.create(this, options);
            }
        }
        return this;
    };
}

$.fn.getTooltipContent = function() {
    return $(this.data(tooltipContent));
};

$.fn.closeTooltip = function() {
    return this.data("keepOpen", 0).tooltip("close");
};

$(() => {
    $(".tooltip-div")
        .click(e => {
            e.preventDefault();
        })
        .each(function() {
            var $this = $(this);
            var id = $this.attr("id");
            var tooltipClass = $this.data("tooltipClass");
            var span = $("<span class='glyphicon glyphicon-question-sign tooltip-icon'/>");

            const uniqueClass = generateUniqueClass("tooltip");
            const uniqueClassSelector = `.${uniqueClass}`;

            function keepOpen() {
                $this.tooltip("open");
            }

            if (tooltipClass) {
                span.addClass(tooltipClass);
            }

            span = span.prependTo(this);

            $this.tooltip({
                content() {
                    return $(".tooltip-text").filter(function() {
                        return $(this).data("for") === id;
                    }).html();
                },
                close() {
                    if ($this.data("keepOpen")) {
                        keepOpen();
                    }
                },
                open() {
                    $(uniqueClassSelector).hover(keepOpen, () => {
                        $this.tooltip("close");
                    });
                },
                items: $this,
                hide: 300,
                tooltipClass: uniqueClass
            }).data(tooltipContent, uniqueClassSelector);

            $("*").click(event => {
                var isKeepOpen = !!$(event.target).closest($this.add(uniqueClassSelector))[0];

                $this.data("keepOpen", isKeepOpen);
                if (isKeepOpen) {
                    keepOpen();
                } else {
                    $this.tooltip("close");
                }
            });
        });
});
