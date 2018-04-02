(async function(window) {
    "use strict";

    const $ = window.$;

    /**
     * @constant {String}
     */
    const AUTO_DELETE = "#auto-delete";

    /**
     * @constant {String}
     */
    const AUTO_MARK = "#auto-mark";

    /**
     * @constant {String}
     */
    const AUTO_DELETE_REMOVE_DELETED = `${AUTO_DELETE}-remove-deleted`;

    /**
     * @constant {String}
     */
    const AUTO_DELETE_NC = `${AUTO_DELETE}-nc`;

    /**
     * @constant {String}
     */
    const AUTO_DELETE_OK = `${AUTO_DELETE}-ok`;

    /**
     * @constant {String}
     */
    const AUTO_DELETE_TEST = `${AUTO_DELETE}-test`;

    /**
     * @constant {String}
     */
    const AUTO_MARK_OK = `${AUTO_MARK}-ok`;

    /**
     * @constant {String}
     */
    const AUTO_MARK_TEST = `${AUTO_MARK}-test`;

    /**
     * @constant {String}
     */
    const AUTO_MARK_TEXT = `${AUTO_MARK}-text`;

    /**
     * @constant {String}
     */
    const AUTO_MARK_TEXT_OMIT = `${AUTO_MARK}-text-omit`;

    /**
     * @constant {String}
     */
    const DELETE_CLASS = ".delete";

    /**
     * @constant {String}
     */
    const MARK_AUTO = ".mark-auto";

    /**
     * @constant {String}
     */
    const NC_ACTION = "ncAction";

    /**
     * @constant {String}
     */
    const NAME = "name";

    /**
     * @constant {String}
     */
    const NC_ROW = ".nc-row";

    /**
     * @constant {String}
     */
    const REASON = "reason";

    /**
     * @constant {String}
     */
    const SELECTED = "selected";

    /**
     * @constant {String}
     */
    const SELECTED_CLASS = `.${SELECTED}`;

    /**
     * @constant {MDialog}
     */
    const MDialog = window.MDialog;

    /**
     * @type {JQuery}
     */
    var autoHideCheckbox;

    /**
     * @type {JQuery}
     */
    var nowCommonsPopupOption;

    /**
     * @type {HTMLElement}
     */
    var ajaxCountElement;

    /**
     * @type {JQuery}
     */
    var autoMarkStart;

    /**
     * @type {JQuery}
     */
    var autoMarkCount;

    /**
     * @type {JQuery}
     */
    var autoDeleteCount;

    /**
     * @type {MDialog}
     */
    var autoDeleteDialog;

    /**
     * @type {MDialog}
     */
    var markDialog;

    /**
     * @jQuery
     */
    var autoUser;

    /**
     * @param {string} name
     */
    function removeRow(name) {
        if (autoHideCheckbox.is(":checked")) {
            $("a.delete").get().forEach(link => {
                var $link = $(link);
                if ($link.data(NAME) === name) {
                    $link.closest(NC_ROW).prev().addBack().remove();
                    let count = +autoMarkCount.attr("max") - 1;

                    [ autoDeleteCount, autoMarkCount, autoMarkStart ].forEach(field => {
                        let $field = $(field);
                        $field.attr("max", count).val(Math.min(+$field.val(), count));
                    });
                }
            });
            setupUploaderDropdown();
        }
    }

    /**
     * @param {Number} decimal
     * @return {Number}
     */
    function roughPercent(decimal) {
        return Math.floor(decimal * 100);
    }

    /**
     * @param {String} string
     * @return {RegExp}
     */
    function parseRegex(string) {
        var lastSlash = string.lastIndexOf("/");

        if (string.indexOf("\n") < 0 && string.match(/^\/.*?\/[mi]*$/)) {
            try {
                return new RegExp(string.substring(1, lastSlash), string.substring(lastSlash + 1));
            } catch (ignored) {}
        }
    }

    /**
     */
    class AbstractMatch {
        /**
         *
         * @abstract
         * @param haystacks
         * @returns
         */
        match() {}
    }

    class UserMatch extends AbstractMatch {
        constructor(user) {
            super();
            /**
             * @private
             */
            this._user = user;
        }

        /**
         *
         * @param haystacks
         * @returns
         */
        match(nowcommonsrow) {
            return nowcommonsrow.uploaders.includes(this._user);
        }
    }

    class CallbackMatch extends AbstractMatch {
        constructor(callback) {
            super();
            this.match = callback;
        }
    }

    class TextMatch extends AbstractMatch {
        constructor() {
            super();
            this.__matchOne = this._matchOne.bind(this);
        }

        /**
         *
         * @param haystacks
         * @returns
         */
        match(nowcommonsrow) {
            var haystacks = nowcommonsrow.texts;
            var result = haystacks.some(this.__matchOne);
            if (this._filter) {
                result = this._filter(result);
            }
            return result;
        }

        setFilter(filter) {
            this._filter = filter;
        }
    }

    class MatchRegex extends TextMatch {
        /**
         * @param {RegExp} regex
         */
        constructor(regex) {
            super();
            this._regex = regex;
        }

        /**
         * @protected
         * @override
         * @param {String} haystack
         * @returns Boolean
         */
        _matchOne(haystack) {
            return haystack.match(this._regex);
        }
    }

    class MatchString extends TextMatch {
        /**
         * @param {string} needle
         */
        constructor(needle) {
            super();
            this._needle = needle;
        }

        /**
         * @override
         * @param {String} haystack
         * @returns {Boolean}
         */
        _matchOne(haystack) {
            return haystack.toUpperCase().indexOf(this._needle) > -1;
        }
    }
    class NowCommonsRow {
        /**
         * @param {JQuery} rows
         */
        constructor(rows) {
            this._div = rows;
            this.texts = $(".text", rows).get().map(textElement => $(textElement).text());
            this.deleteLink = $(DELETE_CLASS, rows);
        }

        /**
         * @return {String}
         */
        get deleteReason() {
            return this.deleteLink.data(REASON);
        }

        /**
         * @return {String}
         */
        get localName() {
            return this.deleteLink.data(NAME);
        }

        /**
         * @return {JQuery}
         */
        get nowCommonsLink() {
            return $(".nowcommons", this._div);
        }
        /**
         * @return {Boolean}
         */
        get selected() {
            return this._div.is(SELECTED_CLASS);
        }

        /**
         * @return {Boolean}
         */
        get hasWarnings() {
            return $(".mark-problematic,.mark-keeplocal,.error-warning", this._div)[0];
        }

        /**
         * @return {Boolean}
         */
        get hasLinks() {
            return $(".linkback", this._div)[0];
        }

        /**
         * @return {Boolean}
         */
        get needsTransfer() {
            return $(".oldver", this._div)[0];
        }

        /**
         * @return {Boolean}
         */
        get hasTalk() {
            return $(".talk", this._div)[0];
        }

        /**
         * @return {String[]}
         */
        get uploaders() {
            return $(".user", this._div).get().map(link => $(link).text());
        }

        /**
         * @param {Boolean} [toggle]
         */
        toggleSelect(toggle) {
            this._div.toggleClass(SELECTED, toggle);
        }
    }

    /**
     * @abstract
     */
    class NowCommonsAction {
        /**
         * @abstract
         * @return {Array.NowCommonsRow} rows
         */
        _run() {}

        run() {
            var rows = this.test().get().slice(this.min(), this.max());

            if (this.confirm(rows.length)) {
                this._run(rows);
            }

            this.postRun(rows);
        }

        /**
         * @protected
         */
        postRun() {}

        /**
         * @protected
         * @param {Number} count
         * @return {Boolean}
         */
        confirm() {
            return true;
        }

        /**
         * @return {Number}
         */
        min() {
            return 0;
        }

        /**
         * @return {Number}
         */
        max() {
            return Number.MAX_VALUE;
        }

        /**
         *
         * @abstract
         * @return {Number}
         */
        test() {}

        /**
         * @final
         * @protected
         * @return {jQuery}
         */
        static getNowCommonsRows(filter) {
            return $(NC_ROW).map(function() {
                var row = new NowCommonsRow($(this).prev(NC_ROW).addBack());
                if (filter(row)) {
                    return row;
                }
            });
        }
    }

    class NowCommonsAbstractAction extends NowCommonsAction {
        /**
         * @param {String} action
         */
        constructor(action) {
            super();
            this._action = action;
        }

        run() {
            return this._action.run.call(this._action);
        }

        test() {
            return this._action.test.call(this._action);
        }
    }

    /**
     * @abstract
     */
    class NowCommonsMarkedAction extends NowCommonsAction {
        /**
         * @override
         * @param {Number} count
         * @return Boolean
         */
        confirm(count) {
            return window.confirm(`Delete ${count} files?`);
        }

        /**
         * @override
         */
        _run(rows) {
            this._markedRun(rows);
        }

        /**
         * @override
         * @returns {Number}
         */
        test() {
            return NowCommonsAction.getNowCommonsRows(row => row.selected);
        }

        /**
         * @override
         * @returns {Number}
         */
        max() {
            return autoDeleteCount.val();
        }

        /**
         * @override
         */
        postRun() {
            autoDeleteDialog.close();
        }
    }

    class NowCommonsSearchAction extends NowCommonsAction {
        /**
         * @override
         * @returns {Number}
         */
        min() {
            return +autoMarkStart.val();
        }

        /**
         * @override
         * @returns {Number}
         */
        max() {
            return +autoMarkCount.val();
        }

        /**
         * @override
         */
        _run(rows) {
            this.rows = rows;
            rows.forEach(row => {
                row.toggleSelect(true);
            });
        }

        /**
         * @override
         */
        test() {
            var searchVal = $(AUTO_MARK_TEXT).val().trim().replace(/\n/g, "").trim();
            var searchRegex = parseRegex(searchVal);
            var omitVal = $(AUTO_MARK_TEXT_OMIT).val().trim().replace(/\n/g, "").trim();
            var omitRegex = parseRegex(omitVal);
            var autoUserVal = autoUser.val().trim();
            var filters = [];
            var omitFilter;

            if (!searchVal && !autoUserVal) {
                throw "Please enter a value into the text box.";
            }

            if (autoUserVal) {
                filters.push(new UserMatch(autoUserVal));
            }
            if (searchVal) {
                filters.push(
                    searchRegex
                        ? new MatchRegex(searchRegex)
                        : new MatchString(searchVal.toUpperCase())
                );
            }
            if (omitVal) {
                omitFilter = omitRegex
                    ? new MatchRegex(omitRegex)
                    : new MatchString(omitVal.toUpperCase());
                omitFilter.setFilter(val => !val);
                filters.push(omitFilter);
            }

            if (!$("#include-warnings:checked")[0]) {
                filters.push(new CallbackMatch(row => !row.hasWarnings));
            }

            if (!$("#include-diff-name:checked")[0]) {
                filters.push(new CallbackMatch(row => !row.hasLinks));
            }

            if (!$("#include-multiple:checked")[0]) {
                filters.push(new CallbackMatch(row => !row.needsTransfer));
            }

            if (!$("#include-talk:checked")[0]) {
                filters.push(new CallbackMatch(row => !row.hasTalk));
            }

            return NowCommonsAction.getNowCommonsRows(
                /**
                 * @param {NowCommonsRow} nowCommonsRow
                 */
                nowCommonsRow => filters.every(
                    /**
                         * @param {AbstractMatch} filter
                         */
                    filter => filter.match(nowCommonsRow)
                )
            );
        }

        /**
         * @override
         */
        postRun() {
            markDialog.close();
        }
    }

    /**
     * @abstract
     */
    class NowCommonsAutoOpenAction extends NowCommonsMarkedAction {
        /**
         * @param {Array.NowCommonsRow} rows
         */
        _markedRun(rows) {
            var getAutoOpenLink = this._getAutoOpenLink.bind(this);
            rows.forEach(row => {
                window.open(getAutoOpenLink(row).attr("href"), "_blank");
                row.toggleSelect(false);
            });
        }

        /**
         * @param {NowCommonsRow[]} 
         * @override
         */
        postRun(rows) {
            rows.forEach(row => removeRow(row.localName));
            super.postRun();
        }
        //        /**
        //         * @callback
        //         * @param {NowCommonsRow} row
        //         * @return {JQuery}
        //         */
        //        _getAutoOpenLink: undefined
    }

    class NowCommonsDeleteAjaxAction extends NowCommonsMarkedAction {
        /**
         * @override
         */
        _markedRun(rows) {
            var ajaxDeleteObject = {};
            rows.forEach(row => {
                var $link = $(row.deleteLink);
                ajaxDeleteObject[$link.data(NAME)] = $link.data(REASON);
            });
            ajaxCountElement.text(+ajaxCountElement.text() + rows.length);
            this.xDomain.postMessage(ajaxDeleteObject);
        }
        //        /**
        //         * @type {XDomain}
        //         */
        //        xDomain : undefined
    }

    class NowCommonsDeletePopupAction extends NowCommonsAutoOpenAction {
        /**
         * @override
         */
        _getAutoOpenLink(row) {
            return row.deleteLink;
        }
    }

    class NowCommonsNowCommonsAction extends NowCommonsAutoOpenAction {
        /**
         * @override
         */
        confirm(count) {
            return window.confirm(`Delete ${count} files?`);
        }

        /**
         * @override
         */
        _getAutoOpenLink(row) {
            return row.nowCommonsLink;
        }
    }

    var markedAction = new NowCommonsAbstractAction(new NowCommonsDeletePopupAction());

    window.XDomain.global(/**
         * @param {XDomain} xDomain
         */
    async xDomain => {
        await xDomain.promise();
        ajaxCountElement.text(0);
        window.topBar({ css: { background: "#000099" }, message: "Ajax delete detected" });
        markedAction._action = new NowCommonsDeleteAjaxAction();

        //wait until document ready in case it hasn't loaded
        $(() => {
            nowCommonsPopupOption.closest(".table-row").hide();
        });

        xDomain.addListener(data => {
            ajaxCountElement.text(ajaxCountElement.text() - 1);
            if (data.status === "success") {
                window.topBar({
                    css: { background: "#009999" },
                    delay: 2000,
                    message: `${data.page}: deleted`
                });
                removeRow(data.page);
            } else {
                window.topBar(`${data.page}: ${data.message}`);
            }
        });
        markedAction._action.xDomain = xDomain;
    });

    window
        .observeDom(() => $.isReady)
        .progress(() => {
            $("#ready-count").html(roughPercent($("input.file").length / $("#files-count").val()));
        });

    $(window).on("beforeunload", () => {
        var selectedLength = $(SELECTED_CLASS).length / 2;
        if (selectedLength) {
            return `You have ${selectedLength} selections outstanding`;
        }
    });

    function setupUploaderDropdown() {
        let previousVal = autoUser.val();
        autoUser.html("").append("<option/>").append(
            Array
                .from(new Set(
                    NowCommonsAction
                        .getNowCommonsRows(() => true)
                        .get()
                        .map(nowcommonsrow => nowcommonsrow.uploaders)
                        ._flatten()
                ))
                .sort()
                .map(uploader => $("<option/>").val(uploader).text(uploader)[0])
        );
        autoUser.val(previousVal);
    }

    await $.ready;

    var closeButtons = $(".close-buttons");
    var testDialog;

    autoUser = $("#auto-user");
    autoHideCheckbox = $(AUTO_DELETE_REMOVE_DELETED);
    nowCommonsPopupOption = $(AUTO_DELETE_NC);
    ajaxCountElement = $(".ajax-count");
    autoMarkStart = $("#auto-mark-start");
    autoMarkCount = $("#auto-mark-count");
    autoDeleteCount = $("#auto-delete-count");

    setupUploaderDropdown();
    $("#doc-load-per,.ready-count,.bottom-buttons").toggle();

    $(MARK_AUTO).click(function() {
        $(this).closest(NC_ROW).toggleClass(SELECTED);
    });

    $("#clear-marks").click(() => {
        $(SELECTED_CLASS).removeClass(SELECTED);
    });

    //suppress default links
    $(`.delink,${MARK_AUTO}`).click(e => {
        e.preventDefault(); //suppress link
    });

    $(`${AUTO_MARK_TEXT},${AUTO_MARK_TEXT_OMIT}`).on("keydown paste drop focus", function() {
        setTimeout(
            () => {
                var jqTextarea = $(this);
                jqTextarea.toggleClass("regex", !!parseRegex(jqTextarea.val()));
            },
            1
        );
    });

    nowCommonsPopupOption.change(() => {
        markedAction._action = nowCommonsPopupOption.is(":checked")
            ? new NowCommonsNowCommonsAction()
            : new NowCommonsDeletePopupAction();
    });

    $(`${AUTO_MARK_OK},${AUTO_MARK_TEST}`).data(NC_ACTION, new NowCommonsSearchAction());
    $(`${AUTO_DELETE_OK},${AUTO_DELETE_TEST}`).data(NC_ACTION, markedAction);

    $(`${AUTO_DELETE_OK},${AUTO_MARK_OK}`).click(function() {
        try {
            $(this).data(NC_ACTION).run();
        } catch (error) {
            window.alert(error);
        }
    });

    $(`${AUTO_MARK_TEST},${AUTO_DELETE_TEST}`).click(function() {
        try {
            $("#count").html($(this).data(NC_ACTION).test().length);
            testDialog.open();
        } catch (error) {
            window.alert(error);
        }
    });

    closeButtons.click(() => {
        $(".bottom-buttons-buttons > *").animate({ width: "toggle" }, 350);
        closeButtons.toggleClass("glyphicon-menu-left");
    });

    testDialog = new MDialog("#auto-files-found");
    testDialog.addCloseButton("#auto-delete-found-ok");

    autoDeleteDialog = new MDialog(AUTO_DELETE, {
        open() {
            let length = markedAction.test().length;
            autoDeleteCount.val(length).attr("max", length);
        }
    });
    autoDeleteDialog.addOpenButton("#auto-delete-open");
    autoDeleteDialog.addCloseButton("#auto-delete-cancel");

    markDialog = new MDialog(AUTO_MARK);
    markDialog.addOpenButton(`${AUTO_MARK}-open`);
    markDialog.addCloseButton(`${AUTO_MARK}-cancel`);
})(window);
