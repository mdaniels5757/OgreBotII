const BASE_DIR = "../public_html";
const CSS_DIR = `${BASE_DIR}/css`;
const JS_DIR = `${BASE_DIR}/js`;

let gulp = require('gulp');
let cleanCSS = require('gulp-clean-css');
let sourcemaps = require('gulp-sourcemaps');
let rename = require("gulp-rename");
let closureCompiler = require('google-closure-compiler').gulp();
let flatmap = require('gulp-flatmap');
let eslint = require('gulp-eslint');
let gulpIf = require('gulp-if');

let getDir = (type) => `../public_html/${type}`;
let getGulpSources = (type) => {
	let dir = getDir(type);
	return gulp.src([`${dir}/*.${type}`, `!${dir}/*.min.${type}`]);
};
let getGulpDest = (type) => gulp.dest(getDir(type));
// Has ESLint fixed the file contents?
let isFixed = (file) => file.eslint != null && file.eslint.fixed;

gulp.task("minify-css", () => {
	console.log("Minifying CSS");
	return getGulpSources("css")
    	.pipe(sourcemaps.init())
	    .pipe(cleanCSS({compatibility: 'ie11'}))
	    .pipe(sourcemaps.write("."))
	    .pipe(rename((path) => {
	    	path.dirname = ".";
	    	if (path.extname === ".css") {
	    		path.extname = ".min.css";	
	    	}
	    }))
	    .pipe(getGulpDest("css"));
}).task("lint-js", () => {
	return getGulpSources("js")
		.pipe(eslint({
			fix: true
		}))
		.pipe(eslint.format())
		.pipe(gulpIf(isFixed, getGulpDest("js")))
		.pipe(eslint.failAfterError());
}).task("minify-js", () => {
	return getGulpSources("js")
	    .pipe(flatmap((stream, file) => {
	    	const filePath = file.relative;
	    	console.log(`Minifying ${filePath}...`);
	    	
			return stream.pipe(closureCompiler({
		    	compilation_level: 'SIMPLE',
	        	//warning_level: 'VERBOSE',
	        	language_in: 'ECMASCRIPT_NEXT',
	        	language_out: 'ECMASCRIPT5',
				js_output_file: filePath.replace(/\.js$/, "") + ".min.js",
				create_source_map: `${filePath}.map`,
				output_wrapper: `%output% //# sourceMappingURL=js/${filePath}.map`,
				source_map_location_mapping: `${JS_DIR}|` 
			}));
	    }))
	    .pipe(getGulpDest("js"));
});