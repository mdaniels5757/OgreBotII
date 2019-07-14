
const tooltipContent = "tooltip-content";

class TooltipsFactory {
    constructor() {
        (async function () {
            await $.ready;
            $(".tooltip-div")
                .click(e => {
                    e.preventDefault();
                })
                .each(function () {
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

                    $this
                        .tooltip({
                            content() {
                                return $(".tooltip-text")
                                    .filter(function () {
                                        return $(this).data("for") === id;
                                    })
                                    .html();
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
                        })
                        .data(tooltipContent, uniqueClassSelector);

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
        })();
    }

    /**
     * 
     * @param {JQuery} jq 
     */
    async close(jq) {
        await $.ready;
        jq.data("keepOpen", 0).tooltip("close");
    }
}