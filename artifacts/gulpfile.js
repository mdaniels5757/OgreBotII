const gulp = require('gulp');
const cleanCSS = require('gulp-clean-css');
const rename = require("gulp-rename");
const closureCompiler = require('google-closure-compiler').gulp();
const flatmap = require('gulp-flatmap');
const prettier = require('gulp-prettier');

const BASE_DIR = "../public_html";

gulp.task("minify-css", () => {
	return gulp.src(`${BASE_DIR}/css/src/*`)
	    .pipe(cleanCSS({compatibility: 'ie11'}))
	    .pipe(rename((path) => {
	    	path.dirname = ".";
	    	if (path.extname === ".css") {
	    		path.extname = ".min.css";	
	    	}
	    }))
	    .pipe(gulp.dest(`${BASE_DIR}/css/bin`));
});

gulp.task("lint-js", () => {
	return gulp.src(`${BASE_DIR}/js/src/*`)
		.pipe(prettier({
			printWidth: 100,
			tabWidth: 4,
			bracketSpacing: true
		}))
		.pipe(gulp.dest(`${BASE_DIR}/js/src`));
});

gulp.task('default', gulp.parallel("minify-css", "lint-js"));