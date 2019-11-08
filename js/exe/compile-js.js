"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const fs_1 = __importDefault(require("fs"));
const io_1 = __importDefault(require("../lib/io"));
const google_closure_compiler_1 = __importDefault(require("google-closure-compiler"));
const promiseUtils_1 = require("../lib/promiseUtils");
const ThreadPool_1 = require("../lib/ThreadPool");
const { compiler: ClosureCompiler } = google_closure_compiler_1.default;
const JS_DIR = `${io_1.default.PROJECT_DIR}/public_html/js`;
const SRC_DIR = `${JS_DIR}/src`;
const DEST_DIR = `${JS_DIR}/bin`;
const MODULES_DIR = `${SRC_DIR}/modules`;
const FILES_TO_MINIFY = process.argv.slice(2);
promiseUtils_1.startup();
(async function () {
    const jsFiles = FILES_TO_MINIFY.length > 0 ?
        FILES_TO_MINIFY.map(file => `${file}.js`) :
        fs_1.default.readdirSync(SRC_DIR).filter(file => file.endsWith(".js"));
    const modules = fs_1.default.readdirSync(MODULES_DIR).map(module => `${MODULES_DIR}/${module}`);
    try {
        //multithreaded Closure makes Windows freeze
        const threadPool = new ThreadPool_1.ThreadPoolImpl(process.platform === "win32" ? 2 : 10);
        await threadPool.enqueueAll(function* () {
            for (const jsFile of jsFiles) {
                yield async () => {
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
                };
            }
        });
        console.log("All files successfully compiled");
    }
    catch {
        console.error("All files NOT successfully compiled");
    }
    finally {
        promiseUtils_1.shutdown();
    }
}());
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiY29tcGlsZS1qcy5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbImNvbXBpbGUtanMudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7Ozs7QUFBQSw0Q0FBZ0M7QUFDaEMsbURBQTJCO0FBQzNCLHNGQUErQztBQUMvQyxzREFBd0Q7QUFDeEQsa0RBQW1EO0FBRW5ELE1BQU0sRUFBQyxRQUFRLEVBQUUsZUFBZSxFQUFDLEdBQUcsaUNBQVEsQ0FBQztBQUU3QyxNQUFNLE1BQU0sR0FBRyxHQUFHLFlBQUUsQ0FBQyxXQUFXLGlCQUFpQixDQUFDO0FBQ2xELE1BQU0sT0FBTyxHQUFHLEdBQUcsTUFBTSxNQUFNLENBQUM7QUFDaEMsTUFBTSxRQUFRLEdBQUcsR0FBRyxNQUFNLE1BQU0sQ0FBQztBQUNqQyxNQUFNLFdBQVcsR0FBRyxHQUFHLE9BQU8sVUFBVSxDQUFDO0FBQ3pDLE1BQU0sZUFBZSxHQUFHLE9BQU8sQ0FBQyxJQUFJLENBQUMsS0FBSyxDQUFDLENBQUMsQ0FBQyxDQUFDO0FBRTlDLHNCQUFPLEVBQUUsQ0FBQztBQUNWLENBQUMsS0FBSztJQUNGLE1BQU0sT0FBTyxHQUFHLGVBQWUsQ0FBQyxNQUFNLEdBQUcsQ0FBQyxDQUFDLENBQUM7UUFDeEMsZUFBZSxDQUFDLEdBQUcsQ0FBQyxJQUFJLENBQUMsRUFBRSxDQUFDLEdBQUcsSUFBSSxLQUFLLENBQUMsQ0FBQyxDQUFDO1FBQzNDLFlBQUUsQ0FBQyxXQUFXLENBQUMsT0FBTyxDQUFDLENBQUMsTUFBTSxDQUFDLElBQUksQ0FBQyxFQUFFLENBQUMsSUFBSSxDQUFDLFFBQVEsQ0FBQyxLQUFLLENBQUMsQ0FBQyxDQUFDO0lBQ2pFLE1BQU0sT0FBTyxHQUFHLFlBQUUsQ0FBQyxXQUFXLENBQUMsV0FBVyxDQUFDLENBQUMsR0FBRyxDQUFDLE1BQU0sQ0FBQyxFQUFFLENBQUMsR0FBRyxXQUFXLElBQUksTUFBTSxFQUFFLENBQUMsQ0FBQztJQUV0RixJQUFJO1FBQ0EsNENBQTRDO1FBQzVDLE1BQU0sVUFBVSxHQUFHLElBQUksMkJBQWMsQ0FBQyxPQUFPLENBQUMsUUFBUSxLQUFLLE9BQU8sQ0FBQyxDQUFDLENBQUMsQ0FBQyxDQUFDLENBQUMsQ0FBQyxFQUFFLENBQUMsQ0FBQztRQUM3RSxNQUFNLFVBQVUsQ0FBQyxVQUFVLENBQUMsUUFBUSxDQUFDO1lBQ2pDLEtBQUssTUFBTSxNQUFNLElBQUksT0FBTyxFQUFFO2dCQUMxQixNQUFNLEtBQUssSUFBSSxFQUFFO29CQUNiLE9BQU8sQ0FBQyxHQUFHLENBQUMsYUFBYSxNQUFNLEtBQUssQ0FBQyxDQUFDO29CQUN0QyxNQUFNLFdBQVcsR0FBRyxNQUFNLENBQUMsT0FBTyxDQUFDLE9BQU8sRUFBRSxFQUFFLENBQUMsR0FBRyxTQUFTLENBQUM7b0JBQzVELE1BQU0sT0FBTyxHQUFHLEdBQUcsTUFBTSxNQUFNLENBQUM7b0JBQ2hDLE1BQU0sVUFBVSxHQUFHLEdBQUcsUUFBUSxJQUFJLFdBQVcsRUFBRSxDQUFDO29CQUNoRCxNQUFNLElBQUksT0FBTyxDQUFDLENBQUMsT0FBTyxFQUFFLE1BQU0sRUFBRSxFQUFFO3dCQUNsQyxJQUFJLGVBQWUsQ0FBQzs0QkFDaEIsRUFBRSxFQUFFLENBQUMsR0FBRyxPQUFPLEVBQUUsR0FBRyxPQUFPLElBQUksTUFBTSxFQUFFLENBQUM7NEJBQ3hDLGlCQUFpQixFQUFFLFFBQVE7NEJBQzNCLDJCQUEyQjs0QkFDM0IsV0FBVyxFQUFFLGlCQUFpQjs0QkFDOUIsWUFBWSxFQUFFLGFBQWE7NEJBQzNCLGNBQWMsRUFBRSxVQUFVOzRCQUMxQixjQUFjLEVBQUUsTUFBTTs0QkFDdEIsaUJBQWlCLEVBQUUsR0FBRyxRQUFRLElBQUksT0FBTyxFQUFFOzRCQUMzQywyQkFBMkIsRUFBRSxHQUFHLE1BQU0sS0FBSzt5QkFDOUMsQ0FBQyxDQUFDLEdBQUcsQ0FBQyxDQUFDLElBQUksRUFBRSxVQUFVLEVBQUUsVUFBVSxFQUFFLEVBQUU7NEJBQ3BDLElBQUksVUFBVSxFQUFFO2dDQUNaLE9BQU8sQ0FBQyxHQUFHLENBQUMsTUFBTSxFQUFFLFVBQVUsQ0FBQyxDQUFDOzZCQUNuQzs0QkFDRCxJQUFJLFVBQVUsRUFBRTtnQ0FDWixPQUFPLENBQUMsS0FBSyxDQUFDLE1BQU0sRUFBRSxVQUFVLENBQUMsQ0FBQTs2QkFDcEM7NEJBQ0QsQ0FBQyxJQUFJLENBQUMsQ0FBQyxDQUFDLE1BQU0sQ0FBQyxDQUFDLENBQUMsT0FBTyxDQUFDLEVBQUUsQ0FBQzt3QkFDaEMsQ0FBQyxDQUFDLENBQUM7b0JBQ1AsQ0FBQyxDQUFDLENBQUM7b0JBQ0gsTUFBTSxZQUFFLENBQUMsU0FBUyxDQUFDLFVBQVUsRUFBRSxHQUFHLFlBQUUsQ0FBQyxHQUFHLHdCQUF3QixPQUFPLEVBQUUsRUFBRSxFQUFFLElBQUksRUFBRSxHQUFHLEVBQUUsQ0FBQyxDQUFDO2dCQUM5RixDQUFDLENBQUM7YUFDTDtRQUNMLENBQUMsQ0FBQyxDQUFDO1FBQ0gsT0FBTyxDQUFDLEdBQUcsQ0FBQyxpQ0FBaUMsQ0FBQyxDQUFDO0tBQ2xEO0lBQUMsTUFBTTtRQUNKLE9BQU8sQ0FBQyxLQUFLLENBQUMscUNBQXFDLENBQUMsQ0FBQztLQUN4RDtZQUFTO1FBQ04sdUJBQVEsRUFBRSxDQUFDO0tBQ2Q7QUFDTCxDQUFDLEVBQUUsQ0FBQyxDQUFDIn0=