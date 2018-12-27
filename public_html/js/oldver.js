/*jslint browser: true, devel: true, sloppy: true */
/*global $, alert, angular, encodeURIComponent */
/**
 * @license
 * Datepicker icon is licensed as BSD by Wikibooks user Tbernhard,
 * https://commons.wikimedia.org/wiki/File:Calendar_-_2.png
 */

//process upload page
if (window.isAngular) {
    angular
        .module("app", [])
        .controller("process-uploads", [
            "$scope",
            $scope => {
                angular.extend($scope, window.logic);
            }
        ])
        .filter("escapeTitle", () => url =>
            encodeURIComponent(url)
                .replace(/%2F/g, "/")
                .replace(/%3A/g, ":")
                .replace(/%20/g, "_")
        )
        .filter("escape", () => encodeURIComponent)
        .filter("trusted", ["$sce", $sce => url => $sce.trustAsResourceUrl(url)]);
} else {
    var submitProcessUploads = $(".submit-pu");
    const notReady = () => {
        alert("Please wait while the page loads.");
    };

    var interval = window.setInterval(() => {
        $(":button")
            .filter(function() {
                return !$(this).data("notReady");
            })
            .data("notReady", true)
            .click(notReady);
    }, 200);

    $(() => {
        var $src = $("#src"),
            $trg = $("#trg"),
            srcSpanVal = $("#srcspan > a").html(),
            trgSpanVal = $("#trgspan > a").html(),
            viewByDate = $(".view-by-date"),
            viewByDateFrom = $("[name='from']", viewByDate),
            viewByDateTo = $("[name='to']", viewByDate),
            datepickerElements;

        const datepickerFormat = "yy-mm-dd";

        function updateTarget() {
            $trg.val($src.val());
        }

        function updateDatepickerMinMax() {
            var from = viewByDateFrom.datepicker("getDate"),
                to = viewByDateTo.datepicker("getDate"),
                startDate = $.datepicker.parseDate(datepickerFormat, $("#start-date").val()),
                endDate = $.datepicker.parseDate(datepickerFormat, $("#end-date").val());

            viewByDateFrom.datepicker("option", { minDate: startDate, maxDate: to || endDate });
            viewByDateTo.datepicker("option", { minDate: from || startDate, maxDate: endDate });
        }

        //jqueryui isn't always loaded
        $.fn.dialog = $.fn.dialog || $.noop;

        clearInterval(interval);
        $(":button").off("click", null, notReady);

        if (srcSpanVal !== undefined) {
            $src.val(srcSpanVal);
            $trg.val(trgSpanVal);
        }

        $src.on("keyup change", updateTarget);

        $trg.on("change", () => {
            $src.off("keyup change", null, updateTarget);
        });

        $("#process_form").submit(() => {
            $(".upload_text").show();

            /* ensure submit button isn't pressed multiple times */
            submitProcessUploads.prop("disabled", true);
        });
        $("#change_files").click(function() {
            $(this)
                .add("#src,#trg,#srcspan,#trgspan,#submit_OV,#upload_text")
                .toggle();
        });

        $("#form").submit(() => {
            /* first valid click... update fields */
            $(".pleaseWait").show();
        });

        datepickerElements = viewByDateFrom.add(viewByDateTo);

        viewByDate = $(".view-by-date").dialog({
            autoOpen: false,
            close: () => {
                datepickerElements.datepicker("destroy");
            },
            modal: true,
            width: 300
        });

        $(".view-by-date-button").click(() => {
            viewByDate.dialog("open");
            datepickerElements.blur().datepicker({
                showOn: "button",
                buttonImage: "images/calendar.png",
                buttonImageOnly: true,
                changeMonth: true,
                changeYear: true,
                dateFormat: datepickerFormat,
                onClose: updateDatepickerMinMax
            });
            updateDatepickerMinMax();
        });

        datepickerElements.change(updateDatepickerMinMax);

        $("#view-by-date-cancel").click(() => {
            viewByDate.dialog("close");
        });

        $("#view-by-date-form")
            .submit(() => {
                if (
                    datepickerElements.is(function() {
                        return !$(this)
                            .val()
                            .match(/\d{4}-\d{2}-\d{2}/);
                    })
                ) {
                    alert("Please enter a date in the form yyyy-mm-dd");
                    return false;
                }
            })
            .on("reset", window.setTimeout.bind(window, updateDatepickerMinMax));
    });

    $("#ident-frame").on("load", function() {
        this.contentWindow.postMessage({ watch: true }, window.location.origin);

        window.onmessage = event => {
            if (event.origin !== location.origin) {
                return;
            }

            submitProcessUploads.toggle(!!event.data.cookie);
            $("#ident-cookie").val(event.data.cookie);
        };
    });
}
