/*******************
 * spinner
*******************/
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
        var coords = this._jq.get().map(elem => {
            const $elem = $(elem);
            var {top, left} = $elem.offset() || { top: 0, left: 0 };
            const bottom = top + $elem.outerHeight(true);
            const right = left + $elem.outerWidth();
            return {top, left, bottom, right};
        });
        const [top, left, bottom, right] = [["top"], ["left"], ["bottom", 1], ["right", 1]].map(
            ([index, max]) => Math[max ? "max" : "min"](...coords.map(coord => coord[index])));
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
        /**
         * @type {Spinner[]}
         */
        this.store = [];
    }

    create(jq, options) {
        this.store.push(new Spinner(jq, options));
        return jq;
    }

    redraw(filterJq = undefined) {
        this.get(filterJq).forEach(spinner => {
            spinner.redraw();
        });
    }

    remove(filterJq = undefined, options = undefined) {
        this.get(filterJq).forEach(spinner => {
            this.store = this.store.filter(removeSpinner => spinner !== removeSpinner);
            spinner.remove(options);
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