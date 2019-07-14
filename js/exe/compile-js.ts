import fs, { exists } from "fs";
import io from "../lib/io";
import compiler from 'google-closure-compiler';

const {compiler: ClosureCompiler} = compiler;

const JS_DIR = `${io.projectDir}/public_html/js`;
const SRC_DIR = `${JS_DIR}/src`;
const DEST_DIR = `${JS_DIR}/bin`;
const MODULES_DIR = `${SRC_DIR}/modules`;
const FILES_TO_MINIFY = process.argv.slice(2);
(async function() {
    const jsFiles = FILES_TO_MINIFY.length > 0 ? 
        FILES_TO_MINIFY.map(file => `${file}.js`) : 
        fs.readdirSync(SRC_DIR).filter(file => file.endsWith(".js"));
    const modules = fs.readdirSync(MODULES_DIR).map(module => `${MODULES_DIR}/${module}`);

    try {
        await Promise.all(jsFiles.map(async jsFile => {
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
                        console.error(jsFile, stdErrData)
                    }
                    (code ? reject : resolve)();
                });
            });
            await io.writeFile(outputFile, `${io.EOL}//# sourceMappingURL=${mapFile}`, {flag: "a"});
        }));
        console.log("All files successfully compiled");
    } catch {
        console.error("All files NOT successfully compiled");
    }
}());
