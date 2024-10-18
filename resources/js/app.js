import '../css/app.css';// 导入全局 CSS 文件
import './bootstrap';// 导入 Bootstrap 相关的 JavaScript 文件

// 从 Inertia.js 和 Vue 3 中导入必要的函数
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { FontAwesomeIcon } from './fontAwesome'; // 导入 FontAwesomeIcon 组件

// 导入 ZiggyVue，用于处理 Laravel 路由
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Solarmax3Wiki';// 获取应用名称，如果未定义则默认为 'Solarmax3Wiki'

// 创建 Inertia 应用
createInertiaApp({
    title: (title) => `${title} - ${appName}`,// 设置页面标题，格式为 "页面标题 - 应用名称"

    // 解析页面组件的函数
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,// 根据名称构建组件路径
            import.meta.glob('./Pages/**/*.vue'),// 动态导入所有页面组件
        ),

    // 设置应用的初始化逻辑
    setup({ el, App, props, plugin }) {
        // 创建 Vue 应用并挂载
        return createApp({ render: () => h(App, props) })// 使用 h 函数渲染 App 组件
            .use(plugin)// 使用 Inertia 插件
            .use(ZiggyVue)// 使用 ZiggyVue 插件以支持 Laravel 路由
            .component('font-awesome-icon', FontAwesomeIcon) // 注册 FontAwesomeIcon 组件
            .mount(el);// 挂载到指定的 DOM 元素
    },

    // 设置进度条的颜色
    progress: {
        color: '#1e3a8a',
    },
});
