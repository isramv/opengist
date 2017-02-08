/**
 * @file
 * Gulp file to compile the SASS files into CSS.
 */

'use strict';

var gulp = require('gulp'),
    sass = require('gulp-sass'),
    livereload = require('gulp-livereload');

gulp.task('sass', function () {
    gulp.src('./sass/**/*.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest('css'));
});

gulp.task('sass2', function () {
    gulp.src('./sass/**/*.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest('css'))
        .pipe(livereload());
});

gulp.task('lr', function () {
    livereload.listen();
    gulp.watch('./sass/**/*.scss', ['sass2']);
});

gulp.task('default', ['lr']);
