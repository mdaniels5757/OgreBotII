"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const fs_1 = __importDefault(require("fs"));
const io_1 = __importDefault(require("../lib/io"));
const google_closure_compiler_1 = __importDefault(require("google-closure-compiler"));
const multithreaded_promise_1 = __importDefault(require("../lib/multithreaded-promise"));
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
        //multithreaded Closure makes Windows freeze
        const multiThreaded = new multithreaded_promise_1.default(process.platform === "win32" ? 2 : 20);
        multiThreaded.enqueue(...jsFiles.map(jsFile => async () => {
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
        await multiThreaded.done();
        console.log("All files successfully compiled");
    }
    catch {
        console.error("All files NOT successfully compiled");
    }
}());
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiY29tcGlsZS1qcy5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbImNvbXBpbGUtanMudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7Ozs7QUFBQSw0Q0FBZ0M7QUFDaEMsbURBQTJCO0FBQzNCLHNGQUErQztBQUMvQyx5RkFBb0U7QUFFcEUsTUFBTSxFQUFDLFFBQVEsRUFBRSxlQUFlLEVBQUMsR0FBRyxpQ0FBUSxDQUFDO0FBRTdDLE1BQU0sTUFBTSxHQUFHLEdBQUcsWUFBRSxDQUFDLFVBQVUsaUJBQWlCLENBQUM7QUFDakQsTUFBTSxPQUFPLEdBQUcsR0FBRyxNQUFNLE1BQU0sQ0FBQztBQUNoQyxNQUFNLFFBQVEsR0FBRyxHQUFHLE1BQU0sTUFBTSxDQUFDO0FBQ2pDLE1BQU0sV0FBVyxHQUFHLEdBQUcsT0FBTyxVQUFVLENBQUM7QUFDekMsTUFBTSxlQUFlLEdBQUcsT0FBTyxDQUFDLElBQUksQ0FBQyxLQUFLLENBQUMsQ0FBQyxDQUFDLENBQUM7QUFDOUMsQ0FBQyxLQUFLO0lBQ0YsTUFBTSxPQUFPLEdBQUcsZUFBZSxDQUFDLE1BQU0sR0FBRyxDQUFDLENBQUMsQ0FBQztRQUN4QyxlQUFlLENBQUMsR0FBRyxDQUFDLElBQUksQ0FBQyxFQUFFLENBQUMsR0FBRyxJQUFJLEtBQUssQ0FBQyxDQUFDLENBQUM7UUFDM0MsWUFBRSxDQUFDLFdBQVcsQ0FBQyxPQUFPLENBQUMsQ0FBQyxNQUFNLENBQUMsSUFBSSxDQUFDLEVBQUUsQ0FBQyxJQUFJLENBQUMsUUFBUSxDQUFDLEtBQUssQ0FBQyxDQUFDLENBQUM7SUFDakUsTUFBTSxPQUFPLEdBQUcsWUFBRSxDQUFDLFdBQVcsQ0FBQyxXQUFXLENBQUMsQ0FBQyxHQUFHLENBQUMsTUFBTSxDQUFDLEVBQUUsQ0FBQyxHQUFHLFdBQVcsSUFBSSxNQUFNLEVBQUUsQ0FBQyxDQUFDO0lBRXRGLElBQUk7UUFDQSw0Q0FBNEM7UUFDNUMsTUFBTSxhQUFhLEdBQUcsSUFBSSwrQkFBd0IsQ0FDOUMsT0FBTyxDQUFDLFFBQVEsS0FBSyxPQUFPLENBQUMsQ0FBQyxDQUFDLENBQUMsQ0FBQyxDQUFDLENBQUMsRUFBRSxDQUFDLENBQUM7UUFDM0MsYUFBYSxDQUFDLE9BQU8sQ0FBQyxHQUFHLE9BQU8sQ0FBQyxHQUFHLENBQUMsTUFBTSxDQUFDLEVBQUUsQ0FBQyxLQUFLLElBQUksRUFBRTtZQUN0RCxPQUFPLENBQUMsR0FBRyxDQUFDLGFBQWEsTUFBTSxLQUFLLENBQUMsQ0FBQztZQUN0QyxNQUFNLFdBQVcsR0FBRyxNQUFNLENBQUMsT0FBTyxDQUFDLE9BQU8sRUFBRSxFQUFFLENBQUMsR0FBRyxTQUFTLENBQUM7WUFDNUQsTUFBTSxPQUFPLEdBQUcsR0FBRyxNQUFNLE1BQU0sQ0FBQztZQUNoQyxNQUFNLFVBQVUsR0FBRyxHQUFHLFFBQVEsSUFBSSxXQUFXLEVBQUUsQ0FBQztZQUNoRCxNQUFNLElBQUksT0FBTyxDQUFDLENBQUMsT0FBTyxFQUFFLE1BQU0sRUFBRSxFQUFFO2dCQUNsQyxJQUFJLGVBQWUsQ0FBQztvQkFDaEIsRUFBRSxFQUFFLENBQUMsR0FBRyxPQUFPLEVBQUUsR0FBRyxPQUFPLElBQUksTUFBTSxFQUFFLENBQUM7b0JBQ3hDLGlCQUFpQixFQUFFLFFBQVE7b0JBQzNCLDJCQUEyQjtvQkFDM0IsV0FBVyxFQUFFLGlCQUFpQjtvQkFDOUIsWUFBWSxFQUFFLGFBQWE7b0JBQzNCLGNBQWMsRUFBRSxVQUFVO29CQUMxQixjQUFjLEVBQUUsTUFBTTtvQkFDdEIsaUJBQWlCLEVBQUUsR0FBRyxRQUFRLElBQUksT0FBTyxFQUFFO29CQUMzQywyQkFBMkIsRUFBRSxHQUFHLE1BQU0sS0FBSztpQkFDOUMsQ0FBQyxDQUFDLEdBQUcsQ0FBQyxDQUFDLElBQUksRUFBRSxVQUFVLEVBQUUsVUFBVSxFQUFFLEVBQUU7b0JBQ3BDLElBQUksVUFBVSxFQUFFO3dCQUNaLE9BQU8sQ0FBQyxHQUFHLENBQUMsTUFBTSxFQUFFLFVBQVUsQ0FBQyxDQUFDO3FCQUNuQztvQkFDRCxJQUFJLFVBQVUsRUFBRTt3QkFDWixPQUFPLENBQUMsS0FBSyxDQUFDLE1BQU0sRUFBRSxVQUFVLENBQUMsQ0FBQTtxQkFDcEM7b0JBQ0QsQ0FBQyxJQUFJLENBQUMsQ0FBQyxDQUFDLE1BQU0sQ0FBQyxDQUFDLENBQUMsT0FBTyxDQUFDLEVBQUUsQ0FBQztnQkFDaEMsQ0FBQyxDQUFDLENBQUM7WUFDUCxDQUFDLENBQUMsQ0FBQztZQUNILE1BQU0sWUFBRSxDQUFDLFNBQVMsQ0FBQyxVQUFVLEVBQUUsR0FBRyxZQUFFLENBQUMsR0FBRyx3QkFBd0IsT0FBTyxFQUFFLEVBQUUsRUFBRSxJQUFJLEVBQUUsR0FBRyxFQUFFLENBQUMsQ0FBQztRQUM5RixDQUFDLENBQUMsQ0FBQyxDQUFDO1FBQ0osTUFBTSxhQUFhLENBQUMsSUFBSSxFQUFFLENBQUM7UUFDM0IsT0FBTyxDQUFDLEdBQUcsQ0FBQyxpQ0FBaUMsQ0FBQyxDQUFDO0tBQ2xEO0lBQUMsTUFBTTtRQUNKLE9BQU8sQ0FBQyxLQUFLLENBQUMscUNBQXFDLENBQUMsQ0FBQztLQUN4RDtBQUNMLENBQUMsRUFBRSxDQUFDLENBQUMifQ==