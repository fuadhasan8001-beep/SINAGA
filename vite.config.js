import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [laravel({ input: ['resources/css/site.css', 'resources/css/site-admin.css', 'resources/css/spmb.css', 'resources/css/admin.css', 'resources/css/admin-redesign.css', 'resources/css/spmb-admin.css', 'resources/js/site.js', 'resources/js/admin.js'], refresh: true })],
});
