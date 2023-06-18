<?php

defined("SAFE_FLAG") or exit(1);

return [
    'hash-key' => 'escXX@laW.gFB]4Sur6p4[F||jPtP`@A1U*(SXKo',

    'allow-upload-files' => ['docx', 'doc', 'pdf', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'],
    
    // redis配置
    'redis' => [
        'hostname' => '127.0.0.1',
        'port' => 6379,
        'database' => 1,
    ],

    // 域名
    'website-host' => 'http://print.cn',
];
