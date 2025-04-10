import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                'camp-blue': '#5FB6FF',
                'camp-red': '#FF5D93',
                'camp-orange': '#FE8B59',
                'camp-green': '#C6FA6C',
                'camp-black': '#000000',
                'camp-gray': '#CCCCCC',
                'headcolor': '#13645E',

                //自定义色卡
                'cua1': '#24345a',//深蓝
                'cua2': '#279de1',//蓝
                'cua3': '#26cdcb',//浅蓝偏绿
                'cua4': '#e7f6f6',//白色
                'cua5': '#ff7f4c',//橙色
                'cua6': '#223242',//深蓝背景

            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [
        forms,
        typography
    ],
};
