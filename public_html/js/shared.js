(window => {
    "use strict";

    const $ = window.$;

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
        /**
         * @param {String} origin
         * @param {String} path the path of the iframe to open
         * @param {Number|null} The timeout after which to fail the request
         */
        constructor(origin, path, timeout) {
            /**
             * @private
             */
            this.origin = origin;

            /**
             * @private
             */
            this.listeners = [];

            /**
             * @private
             */
            this.deferred = $.Deferred();

            function stopListeningForPing() {
                $window.off("message", null, checkedResolved);
                if (failTimeout) {
                    window.clearTimeout(failTimeout);
                }
            }

            function checkOrigin(originalEvent) {
                return originalEvent.origin === origin;
            }

            var checkedResolved = e => {
                var originalEvent = e.originalEvent;

                if (!checkOrigin(originalEvent)) {
                    return;
                }

                if (originalEvent.data === "ping") {
                    this.deferred.resolve();
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
            var failTimeout = timeout && window.setTimeout(
                    () => {
                        this.deferred.reject();
                        stopListeningForPing();
                    },
                    timeout
                );

            $window.on("message", checkedResolved);
            /**
             * @private
             */
            this.iframe = $("<iframe/>")
                .attr({ style: "display: none", src: `${origin}/${path}` })
                .appendTo("body");
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
        promise() {
            return this.deferred.promise();
        }

        /**
         * @param {callback} onDetect
         */
        static global(onDetect) {
            var detected = $();
            window.observeDom(() => {
                var inputs = $("input[data-xd-auto]").not(detected);

                detected = detected.add(inputs);
                inputs.each((index, input) => {
                    const data = $(input).data();
                    onDetect(new XDomain(data.xdDomain, data.xdPath, data.xdTimeout));
                });

                return $.isReady;
            });
        }
    };

    /**
     * @param {Object} options
     */
    window.topBar = function(options) {
        function setHeight(height, div) {
            $(div).css("top", `${height * 26}px`);
        }

        if (typeof options !== "object") {
            options = { message: options };
        }

        var existing = $(".topbar:last");
        var div = $("<div />", { "class": "topbar", text: options.message }).css(options.css || {});

        window.setTimeout(
            () => {
                div.remove();

                $(".topbar").each(setHeight);
            },
            options.delay || 5000
        );

        if (existing[0]) {
            existing.after(div);
        } else {
            $("body").prepend(div);
        }

        setHeight($(".topbar").length - 1, div);
    };

    /**
     * @callback statusCallback
     * @param {number} intervalLength
     */
    window.observeDom = function(statusCallback, intervalLength = 100) {
        var deferred = $.Deferred();
        var interval = setInterval(
            () => {
                if (statusCallback()) {
                    clearInterval(interval);
                    deferred.resolve();
                } else if ($.isReady) {
                    deferred.fail();
                } else {
                    deferred.notify();
                }
            },
            intervalLength
        );

        return deferred.promise();
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

    {
        let FileReader = window.FileReader;
        let defaultOptions = { type: "text" };
        let validReaders = {};
        let validHandlers = {};

        Object.keys(FileReader.prototype).forEach(key => {
            var handler = key.match(/^on([a-z]+)$/);
            var reader = key.match(/^readAs([A-Za-z]+)$/);

            if (handler) {
                validHandlers[key] = handler[1];
            }
            if (reader) {
                validReaders[lcfirst(reader[1])] = key;
            }
        });

        $.fn.fileReader = function(options) {
            var inputs = this.filter("input[type='file']");

            let localHandlers = {};

            options = $.extend({}, options, defaultOptions);
            $.each(validHandlers, function(key) {
                var option = options[this];
                if (option) {
                    localHandlers[key] = option;
                }
            });
            let localReader = validReaders[options.type];

            if (!localReader) {
                throw `Illegal readAs type: ${options.type}`;
            }

            inputs.each(function() {
                //each input gets its own reader
                var $this = $(this);
                var fileReader = new FileReader();

                $.extend(fileReader, localHandlers);

                $this.change(event => {
                    $.each(event.target.files, function() {
                        fileReader[localReader](this);
                    });
                    $this.val("");
                });
            });

            return this;
        };
    }

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
                        Math[max ? "max" : "min"].apply(
                            window,
                            $.map(coords, coords => coords[index])
                        )
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
})(window);
