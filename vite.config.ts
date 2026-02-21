import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

const isDdev = Boolean(process.env.DDEV_PRIMARY_URL);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
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
    server: isDdev
        ? {
              host: '0.0.0.0',
              port: 5174,
              strictPort: false,
              origin:
                  process.env.VITE_SERVER_URI ??
                  `${process.env.DDEV_PRIMARY_URL_WITHOUT_PORT}:5174`,
              cors: {
                  origin: /https?:\/\/([A-Za-z0-9-.]+)?(\.ddev\.site)(?::\d+)?$/,
              },
          }
        : undefined,
});
