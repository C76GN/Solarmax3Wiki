// 修改 vite.config.js 文件

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        rollupOptions: {
            external: [
                'tinymce/tinymce',
                '/tinymce/plugins/wikilink/plugin.min.js'
            ]
        }
    },
    optimizeDeps: {
        include: ['lodash', 'diff', 'dompurify']
    }
});