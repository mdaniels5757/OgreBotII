/*jslint browser: true, devel: true, sloppy: true */
/*global $ */
$($ => {
    function parse(project) {
        return project.match(/^([a-z-]+)\.([a-z-]+)$/);
    }

    function buildSubprojectOptions(currentValue) {
        var subprojectVals = projects[projectElement.val()];

        subprojectElement
            .html(subprojectVals.map(key => `<option>${key}</option>`))
            .val(
                subprojectVals.includes(currentValue)
                    ? currentValue
                    : subprojectElement.children("option").html() || null
            );
    }

    var select = $("select[name='project']");
    var cssClass = select.attr("class");
    var projects = {};

    var [ , currentSubproject, currentProject ] = parse(select.val());

    select.children("option").each(function() {
        var [ , subproject, project ] = parse($(this).html());

        if (!projects[project]) {
            projects[project] = [];
        }
        projects[project].push(subproject);
    });

    var subprojectElement = $("<select/>");
    var projectElement = $("<select/>")
        .append(Object.keys(projects).sort().map(key => `<option>${key}</option>`))
        .val(currentProject);
    projectElement.change(() => {
        buildSubprojectOptions(subprojectElement.val());
    }).change();
    buildSubprojectOptions(currentSubproject);

    select
        .after(
            $("<div style='display: table-row;'/>").append(projectElement, subprojectElement),
            `<input type='hidden' name='project' value='${select.val()}' />`
        )
        .remove();

    select = $("[name='project']");

    projectElement
        .add(subprojectElement)
        .wrap("<div style='display: table-cell'/>")
        .attr("class", cssClass)
        .change(() => {
            select.val(`${subprojectElement.val()}.${projectElement.val()}`);
        });
});
