import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
       laravel({
            input: [
                'resources/scss/rs.scss',
                'resources/js/rs.js',
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                // Bootstrap 5 masih memakai sintaks @import; peringatannya tidak berpengaruh ke hasil
                quietDeps: true,
                silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'mixed-decls'],
            },
        },
    },
});
