class XDomain {
    constructor() {
        /**
         * @private
         */
        this.listeners = [];

        /**
         * @private
         */
        this._promise = new Promise((resolve, reject) => {
            setIntervalImmediate(() => {
                var input = $("input[data-xd-auto]");

                if (input[0]) {
                    let data = $(input).data();

                    var { xdDomain, xdPath, xdTimeout } = data;

                    function stopListeningForPing() {
                        $window.off("message", null, checkedResolved);
                        if (failTimeout) {
                            clearTimeout(failTimeout);
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
                    var failTimeout =
                        xdTimeout &&
                        setTimeout(() => {
                            reject();
                            stopListeningForPing();
                        }, xdTimeout);

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
            }, 100);
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