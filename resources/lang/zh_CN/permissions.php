<?php

// key 是权限的 name 字段 (例如 wiki.view)
return [
    // Wiki 核心权限
    'wiki' => [ // 使用 group 作为第一层 key 方便管理
        'view' => [
            'display_name' => '查看Wiki页面',
            'description' => '允许查看所有公开的Wiki页面',
        ],
        'create' => [
            'display_name' => '创建Wiki页面',
            'description' => '允许创建新的Wiki页面',
        ],
        'edit' => [
            'display_name' => '编辑Wiki页面',
            'description' => '允许编辑自己或他人创建的Wiki页面',
        ],
        'delete' => [
            'display_name' => '删除(移至回收站)Wiki页面',
            'description' => '允许将Wiki页面移至回收站',
        ],
        'history' => [
            'display_name' => '查看Wiki历史',
            'description' => '允许查看页面的编辑历史和版本差异',
        ],
        'revert' => [
            'display_name' => '恢复Wiki版本',
            'description' => '允许将页面恢复到指定的历史版本',
        ],
        'comment' => [
            'display_name' => '评论Wiki页面',
            'description' => '允许在Wiki页面下发表评论和回复',
        ],
        'resolve_conflict' => [
            'display_name' => '解决Wiki冲突',
            'description' => '允许访问冲突解决界面并合并/选择冲突版本',
        ],
        'manage_categories' => [
            'display_name' => '管理Wiki分类',
            'description' => '允许创建、编辑、删除Wiki分类',
        ],
        'manage_tags' => [
            'display_name' => '管理Wiki标签',
            'description' => '允许创建、编辑、删除Wiki标签',
        ],
        'moderate_comments' => [
            'display_name' => '管理Wiki评论',
            'description' => '允许编辑或删除他人的评论',
        ],
    ],

    // Wiki 回收站权限
    'wiki_trash' => [ // group
        'view' => [
            'display_name' => '查看回收站',
            'description' => '允许查看回收站中的页面',
        ],
        'restore' => [
            'display_name' => '恢复回收站页面',
            'description' => '允许从回收站恢复页面',
        ],
        'force_delete' => [
            'display_name' => '永久删除回收站页面',
            'description' => '允许永久删除回收站中的页面',
        ],
    ],

    // 角色管理权限
    'role' => [ // group
        'view' => [
            'display_name' => '查看角色',
            'description' => '允许查看系统中的所有角色及其权限',
        ],
        'create' => [
            'display_name' => '创建角色',
            'description' => '允许创建新的用户角色',
        ],
        'edit' => [
            'display_name' => '编辑角色',
            'description' => '允许编辑现有角色的名称、描述和权限',
        ],
        'delete' => [
            'display_name' => '删除角色',
            'description' => '允许删除用户角色（系统角色除外）',
        ],
    ],

    // 用户管理权限
    'user' => [ // group
        'view' => [
            'display_name' => '查看用户',
            'description' => '允许查看系统中的所有用户列表',
        ],
        'edit' => [
            'display_name' => '编辑用户角色',
            'description' => '允许修改用户的角色分配',
        ],
    ],

    // 日志权限
    'log' => [ // group
        'view' => [
            'display_name' => '查看系统日志',
            'description' => '允许查看系统的活动日志和操作记录',
        ],
    ],
];
