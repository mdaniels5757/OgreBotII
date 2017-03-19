/*jslint browser: true, devel: true, regexp: true  */
/*global $ */
$(function () {
    $("form").one("submit", function () {
        $(this).submit(function () {
            return false;
        });
    });
});