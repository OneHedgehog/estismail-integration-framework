let gulp = require('gulp');
let zip = require('gulp-vinyl-zip').zip; // zip transform only

gulp.task('zip', function () {
    return gulp.src('widget/**/*')
        .pipe(zip('widget.zip'))
        .pipe(gulp.dest('./'));
});

gulp.task('watch', function() {
    gulp.watch('widget/**/*', ['zip']);  // Watch all the .less files, then run the less task
});

gulp.task('default', ['watch']); // Default will run the 'entry' watch task