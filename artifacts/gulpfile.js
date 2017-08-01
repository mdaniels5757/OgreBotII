const BASE_DIR = "../public_html";
const CSS_DIR = `${BASE_DIR}/css`;
const JS_DIR = `${BASE_DIR}/js`;

let gulp = require('gulp');
let cleanCSS = require('gulp-clean-css');
let sourcemaps = require('gulp-sourcemaps');
let rename = require("gulp-rename");
let closureCompiler = require('google-closure-compiler').gulp();
let flatmap = require('gulp-flatmap');


gulp.task("minify-css", () => {
	console.log("Minifying CSS");
	return gulp.src([`${CSS_DIR}/*.css`, `!${CSS_DIR}/*.min.css`])
    	.pipe(sourcemaps.init())
	    .pipe(cleanCSS({compatibility: 'ie11'}))
	    .pipe(sourcemaps.write("."))
	    .pipe(rename((path) => {
	    	path.dirname = ".";
	    	if (path.extname === ".css") {
	    		path.extname = ".min.css";	
	    	}
	    }))
	    .pipe(gulp.dest(CSS_DIR));
}).task("minify-js", () => {
	return gulp.src([`${JS_DIR}/*.js`, `!${JS_DIR}/*.min.js`])
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
	    .pipe(gulp.dest(JS_DIR));
});