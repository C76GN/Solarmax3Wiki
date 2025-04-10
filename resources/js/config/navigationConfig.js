// 定义主导航链接
export const mainNavigationLinks = [
    { href: '/wiki', label: 'Wiki' },
    { href: '#', label: '游戏历史&名人墙' }, // 链接暂未实现，使用 #
    { href: '#', label: '自制专区' },
    { href: '#', label: '攻略专区' },
    { href: '#', label: '论坛' }
];

// 定义后台管理导航链接
export const adminNavigationLinks = [
    { href: '/users', label: '用户管理' },
    { href: '/roles', label: '角色管理' },
    { href: '/wiki/categories', label: '分类管理' },
    { href: '/wiki/tags', label: '标签管理' },
    // 移除模板管理链接
    // { href: '/wiki/templates', label: '模板管理' }
    { href: '/activity-logs', label: '系统日志' } // 添加日志链接（如果需要）
];
