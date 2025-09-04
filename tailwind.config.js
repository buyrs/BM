import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './node_modules/flowbite/**/*.js',
        './node_modules/tw-elements/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                primary: 'var(--primary-color)',
                accent: 'var(--accent-color)',
                secondary: 'var(--secondary-color)',
                'background': 'var(--background-color)',
                white: 'var(--white)',
                'text-primary': 'var(--text-primary)',
                'text-secondary': 'var(--text-secondary)',
                'success-bg': 'var(--success-bg)',
                'success-text': 'var(--success-text)',
                'success-border': 'var(--success-border)',
                'warning-bg': 'var(--warning-bg)',
                'warning-text': 'var(--warning-text)',
                'warning-border': 'var(--warning-border)',
                'error-bg': 'var(--error-bg)',
                'error-text': 'var(--error-text)',
                'error-border': 'var(--error-border)',
                'info-bg': 'var(--info-bg)',
                'info-text': 'var(--info-text)',
                'info-border': 'var(--info-border)',
                'critical-bg': 'var(--critical-bg)',
                'critical-text': 'var(--critical-text)',
                'critical-border': 'var(--critical-border)',
                'medium-bg': 'var(--medium-bg)',
                'medium-text': 'var(--medium-text)',
                'medium-border': 'var(--medium-border)',
                'low-bg': 'var(--low-bg)',
                'low-text': 'var(--low-text)',
                'low-border': 'var(--low-border)',
                gray: {
                    50: '#f9fafb',
                    100: '#f3f4f6',
                    200: '#e5e7eb',
                    300: '#d1d5db',
                    400: '#9ca3af',
                    500: '#6b7280',
                    600: '#4b5563',
                    700: '#374151',
                    800: '#1f2937',
                    900: '#111827',
                },
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
            fontSize: {
                'xs': 'var(--text-xs)',
                'sm': 'var(--text-sm)',
                'lg': 'var(--text-lg)',
                'xl': 'var(--text-xl)',
                '3xl': 'var(--text-3xl)',
                '4xl': 'var(--text-4xl)',
            },
            spacing: {
                '1': 'var(--space-1)',
                '2': 'var(--space-2)',
                '3': 'var(--space-3)',
                '4': 'var(--space-4)',
                '6': 'var(--space-6)',
                '8': 'var(--space-8)',
                '12': 'var(--space-12)',
                'sm': 'var(--padding-sm)',
                'md': 'var(--padding-md)',
                'lg': 'var(--padding-lg)',
                'xl': 'var(--padding-xl)',
            },
            borderRadius: {
                'sm': 'var(--radius-sm)',
                'md': 'var(--radius-md)',
                'lg': 'var(--radius-lg)',
                'xl': 'var(--radius-xl)',
                'full': 'var(--radius-full)',
            },
            boxShadow: {
                'sm': 'var(--shadow-sm)',
                'md': 'var(--shadow-md)',
                'lg': 'var(--shadow-lg)',
                'xl': 'var(--shadow-xl)',
            }
        },
    },

    plugins: [
        forms,
        require('flowbite/plugin'),
        require('tw-elements/plugin.cjs')
    ],
};
