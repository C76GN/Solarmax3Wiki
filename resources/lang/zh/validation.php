<?php

// FileName: /var/www/Solarmax3Wiki/resources/lang/zh/validation.php

return [

    /*
    |--------------------------------------------------------------------------
    | 验证语言行
    |--------------------------------------------------------------------------
    |
    | 以下语言行包含验证类使用的默认错误消息。
    | 某些验证规则有多种版本，例如大小规则。
    | 请随意在这里调整每条消息。
    |
    */

    'accepted' => ':attribute 必须被接受。',
    'accepted_if' => '当 :other 为 :value 时，:attribute 必须被接受。',
    'active_url' => ':attribute 不是一个有效的网址。',
    'after' => ':attribute 必须是 :date 之后的日期。',
    'after_or_equal' => ':attribute 必须是 :date 之后或相同的日期。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能包含字母、数字、短划线和下划线。',
    'alpha_num' => ':attribute 只能包含字母和数字。',
    'array' => ':attribute 必须是一个数组。',
    'ascii' => ':attribute 只能包含单字节字母数字字符和符号。',
    'before' => ':attribute 必须是 :date 之前的日期。',
    'before_or_equal' => ':attribute 必须是 :date 之前或相同的日期。',
    'between' => [
        'array' => ':attribute 必须有 :min 到 :max 项。',
        'file' => ':attribute 必须介于 :min 到 :max KB 之间。',
        'numeric' => ':attribute 必须介于 :min 到 :max 之间。',
        'string' => ':attribute 必须介于 :min 到 :max 个字符之间。',
    ],
    'boolean' => ':attribute 字段必须为 true 或 false。',
    'can' => ':attribute 字段包含未经授权的值。',
    'confirmed' => ':attribute 确认不匹配。',
    'contains' => ':attribute 字段缺少必需的值。',
    'current_password' => '密码不正确。',
    'date' => ':attribute 不是有效日期。',
    'date_equals' => ':attribute 必须是等于 :date 的日期。',
    'date_format' => ':attribute 必须符合格式 :format。',
    'decimal' => ':attribute 必须有 :decimal 位小数。',
    'declined' => ':attribute 必须被拒绝。',
    'declined_if' => '当 :other 为 :value 时，:attribute 必须被拒绝。',
    'different' => ':attribute 和 :other 必须不同。',
    'digits' => ':attribute 必须是 :digits 位数字。',
    'digits_between' => ':attribute 必须是介于 :min 和 :max 位的数字。',
    'dimensions' => ':attribute 图片尺寸无效。',
    'distinct' => ':attribute 字段有重复值。',
    'doesnt_end_with' => ':attribute 不能以以下之一结尾：:values。',
    'doesnt_start_with' => ':attribute 不能以以下之一开头：:values。',
    'email' => ':attribute 必须是有效的电子邮件地址。',
    'ends_with' => ':attribute 必须以以下之一结尾：:values。',
    'enum' => '所选 :attribute 无效。',
    'exists' => '所选 :attribute 无效。',
    'extensions' => ':attribute 必须具有以下扩展名之一：:values。',
    'file' => ':attribute 必须是文件。',
    'filled' => ':attribute 必须有值。',
    'gt' => [
        'array' => ':attribute 必须有多于 :value 项。',
        'file' => ':attribute 必须大于 :value KB。',
        'numeric' => ':attribute 必须大于 :value。',
        'string' => ':attribute 必须多于 :value 个字符。',
    ],
    'gte' => [
        'array' => ':attribute 必须有 :value 项或更多。',
        'file' => ':attribute 必须大于或等于 :value KB。',
        'numeric' => ':attribute 必须大于或等于 :value。',
        'string' => ':attribute 必须大于或等于 :value 个字符。',
    ],
    'hex_color' => ':attribute 必须是有效的十六进制颜色。',
    'image' => ':attribute 必须是图片。',
    'in' => '所选 :attribute 无效。',
    'in_array' => ':attribute 字段不存在于 :other 中。',
    'integer' => ':attribute 必须是整数。',
    'ip' => ':attribute 必须是有效的 IP 地址。',
    'ipv4' => ':attribute 必须是有效的 IPv4 地址。',
    'ipv6' => ':attribute 必须是有效的 IPv6 地址。',
    'json' => ':attribute 必须是有效的 JSON 字符串。',
    'list' => ':attribute 必须是列表。',
    'lowercase' => ':attribute 必须为小写。',
    'lt' => [
        'array' => ':attribute 必须少于 :value 项。',
        'file' => ':attribute 必须小于 :value KB。',
        'numeric' => ':attribute 必须小于 :value。',
        'string' => ':attribute 必须少于 :value 个字符。',
    ],
    'lte' => [
        'array' => ':attribute 不得超过 :value 项。',
        'file' => ':attribute 必须小于或等于 :value KB。',
        'numeric' => ':attribute 必须小于或等于 :value。',
        'string' => ':attribute 必须小于或等于 :value 个字符。',
    ],
    'mac_address' => ':attribute 必须是有效的 MAC 地址。',
    'max' => [
        'array' => ':attribute 不得超过 :max 项。',
        'file' => ':attribute 不得大于 :max KB。',
        'numeric' => ':attribute 不得大于 :max。',
        'string' => ':attribute 不得大于 :max 个字符。',
    ],
    'max_digits' => ':attribute 不能超过 :max 位数。',
    'mimes' => ':attribute 必须是以下类型的文件之一：:values。',
    'mimetypes' => ':attribute 必须是以下类型的文件之一：:values。',
    'min' => [
        'array' => ':attribute 必须至少有 :min 项。',
        'file' => ':attribute 必须至少为 :min KB。',
        'numeric' => ':attribute 必须至少为 :min。',
        'string' => ':attribute 必须至少为 :min 个字符。',
    ],
    'min_digits' => ':attribute 必须至少为 :min 位数。',
    'missing' => ':attribute 必须缺失。',
    'missing_if' => '当 :other 为 :value 时，:attribute 必须缺失。',
    'missing_unless' => '除非 :other 为 :value，否则 :attribute 必须缺失。',
    'missing_with' => '当 :values 存在时，:attribute 必须缺失。',
    'missing_with_all' => '当 :values 存在时，:attribute 必须缺失。',
    'multiple_of' => ':attribute 必须是 :value 的倍数。',
    'not_in' => '所选 :attribute 无效。',
    'not_regex' => ':attribute 格式无效。',
    'numeric' => ':attribute 必须是数字。',
    'password' => [
        'letters' => ':attribute 必须至少包含一个字母。',
        'mixed' => ':attribute 必须包含大小写字母。',
        'numbers' => ':attribute 必须包含至少一个数字。',
        'symbols' => ':attribute 必须包含至少一个符号。',
        'uncompromised' => '提供的 :attribute 在数据泄露中出现过，请选择不同的 :attribute。',
    ],
    'present' => ':attribute 字段必须存在。',
    'prohibited' => ':attribute 字段被禁止。',
    'regex' => ':attribute 格式无效。',
    'required' => ':attribute 字段是必填的。',
    'unique' => ':attribute 已被占用。',
    'url' => ':attribute 必须是有效的 URL。',
    'uuid' => ':attribute 必须是有效的 UUID。',

    'custom' => [
        'attribute-name' => [
            'rule-name' => '自定义消息',
        ],
    ],

    'attributes' => [],

];
