/*global $ */
/*jslint browser: true */
projectMultibox();

var fields = $("#authordate,#license,#fields");
var information = $("#information");

information.click(() => {
    var checked = information.is(":checked");
    if (checked) {
        fields.each(function() {
            $(this).prop("checked", !!this.wasChecked);
        });
    } else {
        fields
            .each(function() {
                this.wasChecked = $(this).prop("checked");
            })
            .prop("checked", false);
    }
    fields.prop("disabled", !checked);
});
