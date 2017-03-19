/*global $ */
/*jslint browser: true */
(() => {
    "use strict";

    var fields = $("#authordate,#license,#fields"),
        information = $("#information");

    information.click(() => {
        var checked = information.is(":checked");
        if (checked) {
            fields.each(function () {
                $(this).prop("checked", !!this.wasChecked);
            });
        } else {
            fields.each(function () {
                this.wasChecked =  $(this).prop("checked");
            }).prop("checked", false);
        }
        fields.prop("disabled", !checked);
    });
})();