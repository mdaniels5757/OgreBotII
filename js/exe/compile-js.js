"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const fs_1 = __importDefault(require("fs"));
const io_1 = __importDefault(require("../lib/io"));
const google_closure_compiler_1 = __importDefault(require("google-closure-compiler"));
const { compiler: ClosureCompiler } = google_closure_compiler_1.default;
const JS_DIR = `${io_1.default.projectDir}/public_html/js`;
const SRC_DIR = `${JS_DIR}/src`;
const DEST_DIR = `${JS_DIR}/bin`;
const MODULES_DIR = `${SRC_DIR}/modules`;
const FILES_TO_MINIFY = process.argv.slice(2);
(async function () {
    const jsFiles = FILES_TO_MINIFY.length > 0 ?
        FILES_TO_MINIFY.map(file => `${file}.js`) :
        fs_1.default.readdirSync(SRC_DIR).filter(file => file.endsWith(".js"));
    const modules = fs_1.default.readdirSync(MODULES_DIR).map(module => `${MODULES_DIR}/${module}`);
    try {
        await Promise.all(jsFiles.map(async (jsFile) => {
            console.log(`Minifying ${jsFile}...`);
            const minFileName = jsFile.replace(/\.js$/, "") + ".min.js";
            const mapFile = `${jsFile}.map`;
            const outputFile = `${DEST_DIR}/${minFileName}`;
            await new Promise((resolve, reject) => {
                new ClosureCompiler({
                    js: [...modules, `${SRC_DIR}/${jsFile}`],
                    compilation_level: 'SIMPLE',
                    //warning_level: 'VERBOSE',
                    language_in: 'ECMASCRIPT_NEXT',
                    language_out: 'ECMASCRIPT5',
                    js_output_file: outputFile,
                    isolation_mode: "IIFE",
                    create_source_map: `${DEST_DIR}/${mapFile}`,
                    source_map_location_mapping: `${JS_DIR}|..`
                }).run((code, stdOutData, stdErrData) => {
                    if (stdOutData) {
                        console.log(jsFile, stdOutData);
                    }
                    if (stdErrData) {
                        console.error(jsFile, stdErrData);
                    }
                    (code ? reject : resolve)();
                });
            });
            await io_1.default.writeFile(outputFile, `${io_1.default.EOL}//# sourceMappingURL=${mapFile}`, { flag: "a" });
        }));
        console.log("All files successfully compiled");
    }
    catch {
        console.error("All files NOT successfully compiled");
    }
}());
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiY29tcGlsZS1qcy5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbImNvbXBpbGUtanMudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7Ozs7QUFBQSw0Q0FBZ0M7QUFDaEMsbURBQTJCO0FBQzNCLHNGQUErQztBQUUvQyxNQUFNLEVBQUMsUUFBUSxFQUFFLGVBQWUsRUFBQyxHQUFHLGlDQUFRLENBQUM7QUFFN0MsTUFBTSxNQUFNLEdBQUcsR0FBRyxZQUFFLENBQUMsVUFBVSxpQkFBaUIsQ0FBQztBQUNqRCxNQUFNLE9BQU8sR0FBRyxHQUFHLE1BQU0sTUFBTSxDQUFDO0FBQ2hDLE1BQU0sUUFBUSxHQUFHLEdBQUcsTUFBTSxNQUFNLENBQUM7QUFDakMsTUFBTSxXQUFXLEdBQUcsR0FBRyxPQUFPLFVBQVUsQ0FBQztBQUN6QyxNQUFNLGVBQWUsR0FBRyxPQUFPLENBQUMsSUFBSSxDQUFDLEtBQUssQ0FBQyxDQUFDLENBQUMsQ0FBQztBQUM5QyxDQUFDLEtBQUs7SUFDRixNQUFNLE9BQU8sR0FBRyxlQUFlLENBQUMsTUFBTSxHQUFHLENBQUMsQ0FBQyxDQUFDO1FBQ3hDLGVBQWUsQ0FBQyxHQUFHLENBQUMsSUFBSSxDQUFDLEVBQUUsQ0FBQyxHQUFHLElBQUksS0FBSyxDQUFDLENBQUMsQ0FBQztRQUMzQyxZQUFFLENBQUMsV0FBVyxDQUFDLE9BQU8sQ0FBQyxDQUFDLE1BQU0sQ0FBQyxJQUFJLENBQUMsRUFBRSxDQUFDLElBQUksQ0FBQyxRQUFRLENBQUMsS0FBSyxDQUFDLENBQUMsQ0FBQztJQUNqRSxNQUFNLE9BQU8sR0FBRyxZQUFFLENBQUMsV0FBVyxDQUFDLFdBQVcsQ0FBQyxDQUFDLEdBQUcsQ0FBQyxNQUFNLENBQUMsRUFBRSxDQUFDLEdBQUcsV0FBVyxJQUFJLE1BQU0sRUFBRSxDQUFDLENBQUM7SUFFdEYsSUFBSTtRQUNBLE1BQU0sT0FBTyxDQUFDLEdBQUcsQ0FBQyxPQUFPLENBQUMsR0FBRyxDQUFDLEtBQUssRUFBQyxNQUFNLEVBQUMsRUFBRTtZQUN6QyxPQUFPLENBQUMsR0FBRyxDQUFDLGFBQWEsTUFBTSxLQUFLLENBQUMsQ0FBQztZQUN0QyxNQUFNLFdBQVcsR0FBRyxNQUFNLENBQUMsT0FBTyxDQUFDLE9BQU8sRUFBRSxFQUFFLENBQUMsR0FBRyxTQUFTLENBQUM7WUFDNUQsTUFBTSxPQUFPLEdBQUcsR0FBRyxNQUFNLE1BQU0sQ0FBQztZQUNoQyxNQUFNLFVBQVUsR0FBRyxHQUFHLFFBQVEsSUFBSSxXQUFXLEVBQUUsQ0FBQztZQUNoRCxNQUFNLElBQUksT0FBTyxDQUFDLENBQUMsT0FBTyxFQUFFLE1BQU0sRUFBRSxFQUFFO2dCQUNsQyxJQUFJLGVBQWUsQ0FBQztvQkFDaEIsRUFBRSxFQUFFLENBQUMsR0FBRyxPQUFPLEVBQUUsR0FBRyxPQUFPLElBQUksTUFBTSxFQUFFLENBQUM7b0JBQ3hDLGlCQUFpQixFQUFFLFFBQVE7b0JBQzNCLDJCQUEyQjtvQkFDM0IsV0FBVyxFQUFFLGlCQUFpQjtvQkFDOUIsWUFBWSxFQUFFLGFBQWE7b0JBQzNCLGNBQWMsRUFBRSxVQUFVO29CQUMxQixjQUFjLEVBQUUsTUFBTTtvQkFDdEIsaUJBQWlCLEVBQUUsR0FBRyxRQUFRLElBQUksT0FBTyxFQUFFO29CQUMzQywyQkFBMkIsRUFBRSxHQUFHLE1BQU0sS0FBSztpQkFDOUMsQ0FBQyxDQUFDLEdBQUcsQ0FBQyxDQUFDLElBQUksRUFBRSxVQUFVLEVBQUUsVUFBVSxFQUFFLEVBQUU7b0JBQ3BDLElBQUksVUFBVSxFQUFFO3dCQUNaLE9BQU8sQ0FBQyxHQUFHLENBQUMsTUFBTSxFQUFFLFVBQVUsQ0FBQyxDQUFDO3FCQUNuQztvQkFDRCxJQUFJLFVBQVUsRUFBRTt3QkFDWixPQUFPLENBQUMsS0FBSyxDQUFDLE1BQU0sRUFBRSxVQUFVLENBQUMsQ0FBQTtxQkFDcEM7b0JBQ0QsQ0FBQyxJQUFJLENBQUMsQ0FBQyxDQUFDLE1BQU0sQ0FBQyxDQUFDLENBQUMsT0FBTyxDQUFDLEVBQUUsQ0FBQztnQkFDaEMsQ0FBQyxDQUFDLENBQUM7WUFDUCxDQUFDLENBQUMsQ0FBQztZQUNILE1BQU0sWUFBRSxDQUFDLFNBQVMsQ0FBQyxVQUFVLEVBQUUsR0FBRyxZQUFFLENBQUMsR0FBRyx3QkFBd0IsT0FBTyxFQUFFLEVBQUUsRUFBQyxJQUFJLEVBQUUsR0FBRyxFQUFDLENBQUMsQ0FBQztRQUM1RixDQUFDLENBQUMsQ0FBQyxDQUFDO1FBQ0osT0FBTyxDQUFDLEdBQUcsQ0FBQyxpQ0FBaUMsQ0FBQyxDQUFDO0tBQ2xEO0lBQUMsTUFBTTtRQUNKLE9BQU8sQ0FBQyxLQUFLLENBQUMscUNBQXFDLENBQUMsQ0FBQztLQUN4RDtBQUNMLENBQUMsRUFBRSxDQUFDLENBQUMifQ==