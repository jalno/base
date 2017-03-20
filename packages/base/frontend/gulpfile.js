var gulp = require("gulp");
var browserify = require("browserify");
var source = require('vinyl-source-stream');
var tsify = require("tsify");
gulp.task("js", function () {
    return browserify({
        basedir: '.',
        debug: false,
        entries: [
			'src/js/Options.ts',
			'src/js/Router.ts',
			'src/js/Translator.ts'
		],
        cache: {},
        packageCache: {},
        external:true
    })
    .plugin(tsify)
    .bundle()
    .pipe(source('scripts.js'))
    .pipe(gulp.dest("dist/js"));
});

gulp.task("default", ["js"], function () {
    
});