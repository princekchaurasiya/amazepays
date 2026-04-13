import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    /** Same Wi‑Fi IP as your PC (e.g. 192.168.1.24). Set in `.env` as `VITE_DEV_HOST` when testing on a phone. */
    const devHost = env.VITE_DEV_HOST || 'localhost';

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.tsx'],
                refresh: true,
            }),
            react(),
        ],
        resolve: {
            alias: {
                '@': '/resources/js',
            },
        },
        server: {
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,
            hmr: {
                host: devHost,
                clientPort: 5173,
            },
        },
    };
});
