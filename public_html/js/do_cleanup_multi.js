/*jslint browser: true, plusplus: true */
(function(window) {
    "use strict";

    const $ = window.$;
    const angular = window.angular;

    if (!angular) {
        /**
         * Opening page
         */
        const src = $("#src");
        const initialSrcVal = src.attr("value");
        const testSearch = $("#test-search");
        const form = $("form");
        const identCookie = $("#ident-cookie");
        const checkboxClicked = () => {
            okButton.attr(
                "disabled",
                $("#checkbox-disclaimers").is(":checked") ? null : "disabled"
            );
        };

        let okButton;

        $("#ident-frame").on("load", function() {
            this.contentWindow.postMessage({ watch: true }, window.location.origin);

            window.onmessage = event => {
                if (event.origin !== location.origin) {
                    return;
                }

                /**
                 * @constant {String}
                 */
                const cookie = event.data.cookie;

                $(".submit_button").toggle(!!cookie);
                $("#verify-text").toggle(!cookie);
                identCookie.val(cookie);
            };
        });

        $("#start,#end").datetimepicker({ dateFormat: "yy/mm/dd", timeFormat: "HH:mm:ss" });

        $("[name='type']").change(function() {
            var typeText = $("#type-text");
            var uploaderOptions = $(".uploader-option");
            var val = $(this).val();
            var wrapper = $("#subcategories-wrapper");
            var wrapperParent = wrapper.parent();

            wrapper.add(testSearch).add(uploaderOptions).hide();
            wrapperParent.css("height", "initial");
            src.val("");
            typeText.text(val.substr(0, 1).toUpperCase() + val.substr(1));
            if (val === "category") {
                wrapper.show();
                src.val(initialSrcVal);
            } else if (val === "search") {
                wrapperParent.css("height", `${wrapperParent.height()}px`);
                testSearch.show();
            } else {
                uploaderOptions.show();
            }
        }).change();

        testSearch.click(() => {
            window.open(
                "//commons.wikimedia.org/w/index.php?title=Special:Search&fulltext=" +
                    `Search&ns6=1&search=${window.encodeURIComponent(src.val())}`,
                "_blank"
            );
        });

        //validation
        form.submit(event => {
            //verify logged in
            if (!identCookie.val()) {
                return false;
            }

            src.val($.trim(src.val()));
            if ($("[name='type']").val() === "category" && !src.val().match(/^category:\S/i)) {
                $("<div/>").html("Please input a valid category").dialog({
                    modal: true,
                    width: 400,
                    buttons: {
                        OK() {
                            $(this).dialog("close");
                        }
                    }
                });
                event.stopImmediatePropagation();
                return false;
            }
        });

        form.submit(function disclaimers() {
            $("#dialog-disclaimers").dialog({
                modal: true,
                width: 600,
                buttons: {
                    OK() {
                        $(this).dialog("close");
                        form.off("submit", disclaimers).submit();
                    },
                    Cancel() {
                        $(this).dialog("close");
                    }
                },
                open() {
                    //FIX ME: don't do this by index...
                    okButton = $("button:eq(1)", $(this).parent());
                    checkboxClicked();
                }
            });

            return false;
        });
        $("#checkbox-disclaimers").change(checkboxClicked);
    } else {
        angular.module("app", []).controller("progress", [
            "$scope",
            "$http",
            "$timeout",
            async ($scope, $http, $timeout) => {
                function prettyTime(time) {
                    var seconds;
                    var minutes;
                    var hours;
                    var asString = "";

                    time = (time / 1000).toFixed(0);
                    seconds = time % 60;
                    time = (time - seconds) / 60;
                    minutes = time % 60;
                    hours = (time - minutes) / 60;

                    if (hours) {
                        asString = `${hours} hours, `;
                    }
                    if (hours || minutes) {
                        asString += `${minutes} minutes, `;
                    }

                    return `${asString}${seconds} seconds`;
                }

                const startTime = new Date();

                $scope.lines = [];
                $scope.changedTotal = () => {
                    return $scope.lines.filter(line => line.changed).length;
                };

                //wait for request_key to be defined from ng-init
                await $timeout();

                if ($scope.error) {
                    return;
                }

                let nextLine = 0;
                let readDataTime;

                (function call() {
                    $http
                        .post(
                            `do_cleanup_multi_json.php?line=${nextLine}&request_key=${$scope.request_key}`
                        )
                        .then(
                            response => {
                                let readData;
                                let data = response.data;

                                if (!data) {
                                    $scope.processError = true;
                                    return;
                                }

                                nextLine = data.lineNum;

                                if (data.startup) {
                                    $scope.started = true;
                                    readData = true;
                                }

                                if (data.count != null) {
                                    $scope.filesCount = data.count;
                                    readData = true;
                                }

                                if (data.lines && data.lines.length) {
                                    $scope.lines = $scope.lines.concat(data.lines);

                                    if ($scope.scrollBottom) {
                                        //scroll to the bottom
                                        window.setTimeout(() => {
                                            window.scrollTo(0, window.document.body.scrollHeight);
                                        });
                                    }
                                    readData = true;
                                }

                                if (data.complete) {
                                    $scope.complete = true;
                                    $scope.runTime = prettyTime(new Date() - startTime);
                                    return;
                                }

                                if (data.error) {
                                    $scope.processError = true;
                                    return;
                                }

                                //sanity check that background process is still running
                                if (readData) {
                                    readDataTime = null;
                                } else {
                                    if (readDataTime) {
                                        if (new Date().getTime() - readDataTime > 300000) {
                                            $scope.processError = true;
                                            return;
                                        }
                                    } else {
                                        readDataTime = new Date().getTime();
                                    }
                                }

                                $timeout(call, 5000);
                            },
                            () => {
                                $scope.processError = true;
                            }
                        );
                })();
            }
        ]).filter("escape", () =>
            url =>
                encodeURIComponent(url)
                    .replace(/%2F/g, "/")
                    .replace(/%3A/g, ":")
                    .replace(
                        /%20/g,
                        "_"
                    )).filter("round", () => (number, precision) => number.toFixed(+precision));
    }
})(window);
