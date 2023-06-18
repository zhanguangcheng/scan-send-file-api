<?php

defined("SAFE_FLAG") or exit(1);
define('APP_PATH', __DIR__);

$config = require __DIR__ . '/config/web.php';
require __DIR__ . '/components/RedisClient.php';
require __DIR__ . '/components/Config.php';
require __DIR__ . '/components/functions.php';

Config::set($config);