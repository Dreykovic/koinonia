import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/error-reporting.js'],
            refresh: true,
        }),
    ],
    build: {
        // Pas de source maps : aucun outil externe ne les recevrait, et "hidden" les
        // écrirait quand même dans public/build (servi publiquement et commité).
        // Le suivi des releases passe par la version jointe à chaque erreur client
        // (meta app-version). Voir docs/logging.md.
        sourcemap: false,
    },
});
