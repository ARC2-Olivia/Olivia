const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

const publicPath = Encore.isProduction() ? '/build' : '/olivia/public/build'

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath(publicPath)
    // only needed for CDN's or subdirectory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */

    // Generic
    .addEntry('app', './assets/app.js')
    .addEntry('quill', './assets/quill.js')
    .addEntry('sortable', './assets/sortable.js')
    .addEntry('axios', './assets/axios.js')
    .addEntry('gridjs', './assets/grid.js')
    .addEntry('popper.dropdown', './assets/scripts/popper.dropdown.js')
    .addEntry('df-select', './assets/df-select.js')
    .addEntry('modal', './assets/scripts/modal.js')
    .addEntry('quiz', './assets/quiz.js')

    // Page specific
    .addEntry('session.extend', './assets/scripts/session.extend.js')
    .addEntry('lessons.sort', './assets/scripts/lessons.sort.js')
    .addEntry('lessons.completion', './assets/lessons.completion.js')
    .addEntry('lessons.quiz.passingPercentage', './assets/scripts/lessons.quiz.passingPercentage.js')
    .addEntry('notes.update', './assets/scripts/notes.update.js')
    .addEntry('evaluation.questions.sort', './assets/scripts/ps.questions.sort.js')
    .addEntry('evaluation.evaluators.sort', './assets/scripts/ps.processors.sort.js')
    .addEntry('ps.pages.sort', './assets/scripts/ps.pages.sort.js')
    .addEntry('ps.processor-groups.sort', './assets/scripts/ps.processor-groups.sort.js')
    .addEntry('index.carousel', './assets/scripts/index.carousel.js')
    .addEntry('tabs.simple', './assets/scripts/tabs.simple.js')

    // Classes
    .addEntry('PracticalSubmoduleAssessment', './assets/scripts/classes/PracticalSubmoduleAssessment.js')
    .addEntry('Reorderer', './assets/scripts/classes/Reorderer.js')
    .addEntry('EventBus', './assets/scripts/classes/EventBus.js')

    .copyFiles({ from: './assets/images', to: 'images/[path][name].[ext]' })

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    //.enableStimulusBridge('./assets/controllers.json')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // configure Babel
    // .configureBabel((config) => {
    //     config.plugins.push('@babel/a-babel-plugin');
    // })

    // enables and configure @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })

    // enables Sass/SCSS support
    //.enableSassLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()
;

module.exports = Encore.getWebpackConfig();
