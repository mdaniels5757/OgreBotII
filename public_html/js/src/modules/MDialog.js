class MDialog {
    /**
     * @param {JQuery} jq
     * @param {Object} options
     */
    constructor(jq, options) {
        /**
         * @type {JQuery}
         */
        this.dialog = $(jq).dialog(
            {autoOpen: false, modal: true, width: 600 , ...options}
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