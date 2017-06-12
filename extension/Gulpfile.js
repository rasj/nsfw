const gulp = require('gulp'),
	babel = require('gulp-babel'),
	clean = require('gulp-clean'),
	cleanhtml = require('gulp-cleanhtml'),
	minifycss = require('gulp-minify-css'),
	jshint = require('gulp-jshint'),
	stripdebug = require('gulp-strip-debug'),
	uglify = require('gulp-uglify'),
	zip = require('gulp-zip'),
	browserify = require('gulp-browserify'),
	gulpSequence = require('gulp-sequence').use(gulp),
	gutil = require('gulp-util');

function swallowError (error)
{
  console.log(error.stack);
  this.emit('end')
}

//clean dist directory
gulp.task('clean', () => {
	return gulp.src('dist/*', {read: false})
		.pipe(clean());
});

//copy static folders to dist directory
gulp.task('copy', () => {
	return gulp.src('src/**')
		.pipe(gulp.dest('dist/'));
});

//copy and compress HTML files
gulp.task('html', () => {
	return gulp.src('src/**/*.html')
		.pipe(cleanhtml())
		.pipe(gulp.dest('dist'));
});


gulp.task('scripts', [], () => {
	return gulpSequence('scripts:browserify','scripts:minify')();
});

gulp.task('scripts:browserify-all', () => {
	return gulp.src(['src/*.js','src/scripts/**/*.js','!src/updater.js'])
		.pipe(jshint({
			esversion: 6
		}))
		.pipe(jshint.reporter('default'))
		.pipe(babel({
            presets: ['es2015']
        })).on('error',swallowError)
        // .pipe(browserify({
        //   insertGlobals : true,
        //   debug: true
        // }))
        .on('error',swallowError)
		.pipe(gulp.dest('dist/'));
});

gulp.task('scripts:browserify-ignore', () => {
	return gulp.src(['src/updater.js'])
		.pipe(gulp.dest('dist/'));
});

gulp.task('scripts:browserify', ['scripts:browserify-all','scripts:browserify-ignore']);

gulp.task('scripts:minify', [], () => {
	return gulp.src(['dist/*.js'])
		.pipe(stripdebug())
		.pipe(babel({
            presets: ['es2015']
        })).on('error',swallowError)
		.pipe(uglify({outSourceMap: true}))
		.on('error',swallowError)
		.pipe(gulp.dest('dist/'));
});
		
//minify styles
gulp.task('styles', () => {
	return gulp.src('src/styles/**')
		.pipe(gulp.dest('dist/styles'));
});

//dist ditributable and sourcemaps after other tasks completed
gulp.task('build', ['clean','copy','html', 'scripts', 'styles'], () => {
	var manifest = require('./src/manifest.json'),
		distFileName = 'v' + manifest.version + '.zip';
	
	return gulp.src(['dist/**', '!dist/**/*.map'])
		.pipe(zip(distFileName))
		.pipe(gulp.dest('build'));
});

//run all tasks after dist directory has been cleaned
gulp.task('default', ['build']);

gulp.task('watch', ['scripts:browserify','html','styles'], () => {
	gulp.watch(['src/**/*.js','!src/updater.js'],{read:false},['scripts:browserify']).on('error',swallowError);
	gulp.watch('src/**/*.html',{read:false},['html']).on('error',swallowError);
	gulp.watch('src/styles/**/*.scss',{read:false},['styles']).on('error',swallowError);
});