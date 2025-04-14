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
    { href: '/dashboard', label: '仪表盘', permission: 'log.view' }, // 添加仪表盘（如果需要权限控制）
    { href: '/users', label: '用户管理', permission: 'user.view' },
    { href: '/roles', label: '角色管理', permission: 'role.view' },
    { href: '/wiki/categories', label: '分类管理', permission: 'wiki.manage_categories' },
    { href: '/wiki/tags', label: '标签管理', permission: 'wiki.manage_tags' },
    { href: '/wiki/trash', label: '回收站', permission: 'wiki.trash.view' }, // 添加回收站链接
    { href: '/activity-logs', label: '系统日志', permission: 'log.view' },
];
