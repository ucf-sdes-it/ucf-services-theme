System.register(["./app-student-services/app-student-services.component", "./pipes/unescapeHtml.pipe"], function (exports_1, context_1) {
    "use strict";
    var __moduleName = context_1 && context_1.id;
    function exportStar_1(m) {
        var exports = {};
        for (var n in m) {
            if (n !== "default")
                exports[n] = m[n];
        }
        exports_1(exports);
    }
    return {
        setters: [
            function (app_student_services_component_1_1) {
                exportStar_1(app_student_services_component_1_1);
            },
            function (unescapeHtml_pipe_1_1) {
                exportStar_1(unescapeHtml_pipe_1_1);
            }
        ],
        execute: function () {
        }
    };
});
//# sourceMappingURL=index.js.map