/*jslint browser: true, devel: true, plusplus: true, nomen: true */
/*global $, saveAs */
const requestKey = $("#request_key").val();

var panels = $(".panel");
var next = $("#next");
var previous = $("#previous");
var submit = $("#submit");
var typeCheckbox = $("[name='type']");
var startForm = $("#start-form");
var phaseInput = $("#phase");
const returnUserStorageName = "magog.branchmapper.returnuser";
var newUserInstructions = $(".newuser-instructions");
var saveState = $("#save-state");
var allMapLinksDiv = $(".all-map-links");
var allMapLinksToggle = $("#all-map-links-toggle");
const saveStateStorageName = "magog.branchmapper.savestate";
const stringify = JSON.stringify.bind(JSON);
var bodyWrapper = $(".body-wrapper");
const noop = $.noop;
var finalDownloadListener = noop;

function getPhase() {
    return +phaseInput.val();
}

function setPhase(i) {
    phaseInput.val(i);
}

function updatePhase(delta) {
    var phase = getPhase() + delta,
        phaseLast = phase === panels.length - 1,
        checkedVal = typeCheckbox.filter(":checked").val(),
        element = checkedVal ? $(`#branch-${checkedVal}`) : 0;

    setPhase(phase);
    panels.hide().eq(phase).show();
    previous.add(saveState).toggle(phase !== 0);
    next.toggle(!phaseLast);
    submit.toggle(phaseLast);

    if (checkedVal) {
        $(".branch-div").hide();
        element.show().closest(".panel");
    }
}

function showStartupHelpDialog() {
    newUserInstructions.dialog({
        modal: true,
        width: 800,
        buttons: {
            OK() {
                $(this).dialog("close");
            }
        },
        close() {
            localStorage.setItem(returnUserStorageName, Date.now());
        }
    });
}

function getSavedStateFromText(text) {
    var states = text ? $.parseJSON(text) : {};

    if (!$.isPlainObject(states)) {
        throw "Deserialization failed!";
    }

    return window.sortByKey(states);
}

function getSavedState() {
    return getSavedStateFromText(localStorage.getItem(saveStateStorageName));
}

function setSavedState(state) {
    localStorage.setItem(saveStateStorageName, stringify(state));
}

startForm.submit(() => {
    //hitting enter key on another phase
    if (!submit.is(":visible")) {
        return false;
    }

    bodyWrapper.spinner();
    $(":submit").attr("disabled", "disabled");
});

updatePhase(0);

previous.click($.proxy(updatePhase, previous, -1));

next
    .click($.proxy(updatePhase, previous, 1))
    .attr("disabled", typeCheckbox.filter(":checked")[0] ? null : "disabled");

typeCheckbox.one("change", $.fn.attr.bind(next, "disabled", null));

$(".download-button").click(function() {
    var button = $(this),
        row = button.closest("[data-map-name]"),
        map = row.data("mapName"),
        svgOptions = $(".svg-options");

    finalDownloadListener = async () => {
        var thisSpinner = button.closest("tr").children(".map,.selection,.resolution").spinner();

        svgOptions.dialog("close");

        try {
            await $.fileDownload("download.php", {
                cookieName: `branchmapper-${requestKey}-${map}`,
                cookiePath: $("#cookie-path").val(),
                checkInterval: 1000,
                data: {
                    request_key: requestKey,
                    map: map,
                    color: $("#option-color").val(),
                    radius: $("#option-radius").val()
                },
                httpMethod: "post",
                failMessageHtml: `Unable to download ${row.data("mapHumanReadable")}. ` +
                    "Please reload the page and try again."
            });
        } finally {
            thisSpinner.spinner("remove");
        }
    };

    svgOptions.dialog({
        close() {
            finalDownloadListener = noop;
        },
        modal: true,
        width: 600,
        height: 400
    });
    $(
        "#recommended-dimensions"
    ).html(`${row.data("mapRecommendedWidth")}` + `&nbsp;×&nbsp;${row.data("mapRecommendedHeight")}`);
});
$(".resolution :checkbox").click(function() {
    var $this = $(this);
    $this.siblings(":text").toggle(!$this.is(":checked"));
});
$(".show-wikitext").click(async function(e) {
    //cancel anchor click
    e.preventDefault();
    await $(this).closest(".wikitext").find(".wikitext-toggle").each(function() {
        var $this = $(this);
        $this.slideToggle($this.height());
    }).promise();
    $.spinner.all.redraw();
});
$("#verify-input").click(async () => {
    bodyWrapper.spinner();
    try {
        let data = await $.ajax("check.php", { data: startForm.serialize(), method: "post" });
        let message = data && data.message;
        if (message) {
            $("#verify-text").val(message);
        } else {
            window.alert(data && data.error || "Connection problem or server error.");
        }
    } catch (e) {
        window.alert("Connection problem");
    } finally {
        bodyWrapper.spinner("remove");
    }
});
$("#final-download").click(() => {
    finalDownloadListener();
}); //has this user been asked within the last 30 days?
if ((localStorage.getItem(returnUserStorageName) || 0) < Date.now() - 1000 * 60 * 60 * 24 * 30) {
    showStartupHelpDialog();
}
$("#click-help").click(function() {
    showStartupHelpDialog(); //cancel sticky popup
    setTimeout($.fn.closeTooltip.bind($(this)), 1);
});
$("#load-state").click(function() {
    var div = $("<div/>");
    var table = $("<table class='load-session'/>").clone().show();
    var state = getSavedState();
    $.each(state, (name, entry) => {
        var loadButton = $("<input type='button' value='Load'  class='btn btn-primary' />").click((
            
        ) =>
            {
                typeCheckbox
                    .filter(`[value='${entry.type}']`)
                    .prop("checked", true); //TODO use Object.entries
                $.each(entry.inputs, (key, value) => {
                    $(`textarea[name='${key}']`).val(value);
                });
                setPhase(1);
                updatePhase(0);
                div.dialog("close");
            });
        var removeButton = $(
            "<input type='button' value='Remove' class='btn btn-warning' />"
        ).click(() => {
            removeButton.closest("tr").remove();
            delete state[name];
            setSavedState(state);
        });
        table.append(
            $("<tr/>")
                .append($("<td/>").append(loadButton))
                .append($("<td/>").append(removeButton))
                .append($("<td/>").text(name))
        );
    });
    div.append(table).dialog({
        modal: true,
        width: 800,
        title: "Select a session",
        buttons: {
            Cancel() {
                $(this).dialog("close");
            }
        }
    });
});
saveState.click(() => {
    const prefix = "Saved ";
    var type = typeCheckbox.filter(":checked").val();
    var inputs = $(`#branch-${type} textarea`);
    var state = getSavedState();
    var defaultName = 0;
    var name;
    var saveStateObject; /**
         * get name
         */
    while (state[prefix + ++defaultName]) {}
    do {
        name = prompt("Name of the saved state: ", `${prefix}${defaultName}`); //cancelled
        if (name === null) {
            return;
        }
        if (state[name]) {
            if (window.confirm("The name already exists. Overwrite?")) {
                state[name] = 0;
            }
        }
    } while (state[name]);
    saveStateObject = { type: type, inputs: {} };
    inputs.each(function() {
        var $this = $(this);
        saveStateObject.inputs[$this.attr("name")] = $this.val();
    });
    state[name] = saveStateObject;
    setSavedState(state);
});
var $import = $("#import").change(event => {
    let fileReader = new FileReader();
    fileReader.onload = progressEvent => {
        try {
            let text = progressEvent.target.result;
            var importCount = 0;
            var state = getSavedState();
            Object.entries(getSavedStateFromText(text)).forEach(([ key, newState ]) => {
                var inputs = newState.inputs;
                if (
                    !inputs || typeof inputs !== "object" ||
                        Object.values(inputs).some(val => typeof val !== "string")
                ) {
                    throw "Deserialization failed.";
                }
                if (!state[key] || window.confirm(`Overwrite ${key}?`)) {
                    importCount++;
                    state[key] = newState;
                }
            });
            setSavedState(state);
            window.alert(`${importCount} entries loaded.`);
            $import.val("");
        } catch (e) {
            window.alert("Er, that didn't seem to work.");
        }
    };
    fileReader.readAsText(event.target.files[0]);
});
$("#export").click(() => {
    saveAs(
        new Blob([ localStorage.getItem(saveStateStorageName) ], {
            type: "text/plain;charset=utf-8"
        }),
        "branchmapper-data.txt"
    );
});
allMapLinksToggle.click(async e => {
    //cancel click
    e.preventDefault();
    await allMapLinksDiv.slideToggle(50).promise();
    allMapLinksToggle.text(allMapLinksDiv.is(":hidden") ? "+" : "-");
});
