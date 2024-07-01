import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});

mix.options({
    hmrOptions: {
        host: 'laravelgitrepos-production-bfe9.up.railway.app',
        port: 443
    }
});
