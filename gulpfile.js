/**
 * gulpfile.js — SIGA-COCIAP
 * Compilador de assets frontend.
 * Uso:
 *   gulp        → compilar y vigilar cambios (desarrollo)
 *   gulp build  → compilar una sola vez (producción)
 */

const gulp        = require('gulp');
const sass        = require('gulp-sass')(require('sass'));
const plumber     = require('gulp-plumber');
const autoprefixer= require('gulp-autoprefixer');
const cleanCSS    = require('gulp-clean-css');
const concat      = require('gulp-concat');
const uglify      = require('gulp-uglify');
const browserSync = require('browser-sync').create();

// ── Rutas del proyecto ───────────────────────────────────────
const paths = {
    sass: {
        src:  'resources/sass/**/*.scss',
        dest: 'public/css/'
    },
    js: {
        src:  'resources/js/**/*.js',
        dest: 'public/js/'
    },
    php: {
        watch: '**/*.php'   // para recargar el navegador al guardar PHP
    }
};

// ── Configuración de BrowserSync ────────────────────────────
const proxyURL = 'localhost/siga-cociap/public';

// ── Tarea: compilar SASS ─────────────────────────────────────
function compilarSass() {
    return gulp
        .src('resources/sass/app.scss')   // archivo principal de entrada
        .pipe(plumber({
            errorHandler: function(err) {
                console.error('❌ Error SASS:', err.message);
                this.emit('end');
            }
        }))
        .pipe(sass({ outputStyle: 'expanded' }))
        .pipe(autoprefixer({ overrideBrowserslist: ['last 2 versions'] }))
        .pipe(cleanCSS({ level: 1 }))
        .pipe(gulp.dest(paths.sass.dest))
        .pipe(browserSync.stream());      // inyecta CSS sin recargar la página
}

// ── Tarea: compilar JS ───────────────────────────────────────
function compilarJs() {
    return gulp
        .src([
            'resources/js/app.js',        // primero el archivo principal
            'resources/js/**/*.js'        // luego el resto
        ])
        .pipe(plumber())
        .pipe(concat('app.js'))
        .pipe(uglify())
        .pipe(gulp.dest(paths.js.dest))
        .pipe(browserSync.stream());
}

// ── Tarea: iniciar BrowserSync ───────────────────────────────
function servidor(done) {
    browserSync.init({
        proxy: proxyURL,
        notify: false,      // sin el banner de BS en la pantalla
        open: true,         // abre el navegador automáticamente
        port: 3000
    });
    done();
}

// ── Tarea: recargar navegador al cambiar PHP ─────────────────
function recargarPhp(done) {
    browserSync.reload();
    done();
}

// ── Tarea: vigilar cambios ───────────────────────────────────
function vigilar() {
    gulp.watch(paths.sass.src, compilarSass);
    gulp.watch(paths.js.src,   compilarJs);
    gulp.watch(paths.php.watch, recargarPhp);
}

// ── Exportar tareas ──────────────────────────────────────────

// gulp build → compilar una sola vez
exports.build = gulp.series(compilarSass, compilarJs);

// gulp → desarrollo completo con live reload
exports.default = gulp.series(
    compilarSass,
    compilarJs,
    servidor,
    vigilar
);
