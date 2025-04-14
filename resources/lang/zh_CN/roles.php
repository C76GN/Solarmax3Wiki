<?php

return [
    // 角色名称映射 (key 是角色的 name 字段)
    'admin' => [
        'display_name' => '管理员',
        'description' => '系统管理员，拥有所有权限',
    ],
    'editor' => [
        'display_name' => '编辑',
        'description' => '内容编辑者，可以创建、编辑、评论页面',
    ],
    'conflict_resolver' => [
        'display_name' => '冲突解决者',
        'description' => '具有解决Wiki页面编辑冲突的权限',
    ],
    // 添加其他你可能定义的角色
];
