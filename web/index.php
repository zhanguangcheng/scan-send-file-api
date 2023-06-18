<?php

define("SAFE_FLAG", true);
require_once __DIR__ . '/../init.php';

$action = get('action', 'qrcode');
switch ($action) {
    case 'qrcode': echo qrcode();break;
    case 'query': echo query();break;
    case 'upload': echo upload();break;
    case 'delete': echo delete();break;
    default: echo stdResponse(400, "参数错误");break;
}

function qrcode()
{
    $expire = 30 * 60;
    if (isset($_COOKIE['taskid'])) {
        $id = dataDecode($_COOKIE['taskid']);
    }
    if (isset($id)) {
        $taskid = $_COOKIE['taskid'];
    } else {
        $id = generateRandomKey(16);
        $taskid = dataEncode($id, $expire);
        setcookie('taskid', $taskid, time() + $expire);
    }

    $key = "task:$id";
    $redis = redis();
    $exists = $redis->exists($key);
    if (!$exists) {
        $redis->hset($key, 'status', 0);
        $redis->expire($key, $expire);
        $files = [];
    } else {
        $_files = $redis->hget($key, 'files');
        $files = $_files ? json_decode($_files, true) : [];
    }

    $url = config('website-host') . "/#/qrcode?taskid=$taskid";
    return stdResponse(200, '', [
        'url' => $url,
        'files' => $files,
    ]);
}

function query()
{
    if (empty($_COOKIE['taskid'])) {
        return stdResponse(401, '二维码已过期');
    }
    $id = dataDecode($_COOKIE['taskid']);
    if (!$id) {
        return stdResponse(401, '二维码已过期');
    }
    $key = "task:$id";
    $redis = redis();
    if (!$redis->hget($key, 'status')) {
        return stdResponse(200, '', ['status' => 0]);
    }
    $redis->hset($key, 'status', 0);
    $_files = $redis->hget($key, 'files');
    $files = json_decode($_files, true);
    return stdResponse(200, '', [
        'status' => 1,
        'files' => $files
    ]);
}

function delete()
{
    if (empty($_COOKIE['taskid'])) {
        return stdResponse(400, '二维码已过期');
    }
    $id = dataDecode($_COOKIE['taskid']);
    if (!$id) {
        return stdResponse(400, '二维码已过期');
    }
    $key = "task:$id";
    $uid = post('uid');
    if (empty($uid)) {
        return stdResponse(400, '参数不能为空');
    }
    $redis = redis();
    $_files = $redis->hget($key, 'files');
    $files = $_files ? json_decode($_files, true) : [];
    $files = array_values(array_filter($files, function($v) use($uid) {
        return $v['uid'] != $uid;
    }));
    $filesStringify = json_encode_unicode($files);
    $redis->hset($key, 'files', $filesStringify);
    return stdResponse(200, '删除成功');
}

function upload()
{
    $taskid = get('taskid');
    if (!$taskid) {
        return stdResponse(400, '参数错误');
    }
    $id = dataDecode($taskid);
    if (!$id) {
        return stdResponse(400, '二维码已失效，请重试');
    }
    $key = "task:$id";
    $redis = redis();
    $exists = $redis->exists($key);
    if (!$exists) {
        return stdResponse(400, '二维码已失效，请重试');
    }
    $file = files('file');
    if (empty($file)) {
        return stdResponse(400, '请上传文件或文件太大');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return stdResponse(400, '非法文件上传');
    }
    if ($file['size'] > 10 << 20) {
        return stdResponse(400, '文件不能大于10MB');
    }
    $ext = trim(strrchr($file['name'], '.'), '.');
    $allowExts = config('allow-upload-files', []);
    if (!in_array($ext, $allowExts)) {
        return stdResponse(400, "不允许上传的文件类型:$ext");
    }
    $uid = generateRandomKey(16);
    $filename = '/attachment/' . date('Ym/d/') . "$id.$ext";
    $webroot = APP_PATH . '/web';
    if (!is_dir(dirname("$webroot$filename"))) {
        mkdir(dirname("$webroot$filename"), 0775, true);
    }
    if (!move_uploaded_file($file['tmp_name'], "$webroot$filename")) {
        return stdResponse(400, '文件保持失败');
    }
    $_files = $redis->hget($key, 'files');
    $files = $_files ? json_decode($_files, true) : [];
    $files[] = ['uid' => $uid, 'name' => $file['name'], 'url' => $filename, 'size' => $file['size']];
    $filesStringify = json_encode_unicode($files);
    $redis->hmset($key, 'status', 1, 'files', $filesStringify);
    return stdResponse(200, '文件上传成功');
}
