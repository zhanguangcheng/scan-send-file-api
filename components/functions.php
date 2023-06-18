<?php

function redis()
{
    static $redis;
    if (!$redis) {
        $redis = new RedisClient(config('redis'));
    }
    return $redis;
}

function config($key = null, $defaultValue = null)
{
    return Config::get($key, $defaultValue);
}

function get($name = null, $defaultValue = null)
{
    if ($name === null) {
        return $_GET;
    }
    return isset($_GET[$name]) ? $_GET[$name] : $defaultValue;
}

function post($name = null, $defaultValue = null)
{
    if ($name === null) {
        return $_POST;
    }
    return isset($_POST[$name]) ? $_POST[$name] : $defaultValue;
}

function isPost()
{
    return isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'POST');
}

function files($name = null)
{
    if ($name === null) {
        return $_FILES;
    }
    return isset($_FILES[$name]) ? $_FILES[$name] : null;
}

function stdResponse($code, $message = '', $result = [])
{
    header('Content-Type: application/json');
    return json_encode_unicode([
        'code' => $code,
        'message' => $message,
        'result' => $result,
    ]);
}

/**
 * 兼容参数:JSON_UNESCAPED_UNICODE 的json编码函数
 * @param  mixed $data
 * @return string
 */
function json_encode_unicode($data)
{
    if (defined('JSON_UNESCAPED_UNICODE')) {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    array_walk_recursive($data, function (&$item) {
        if (is_string($item)) {
            $item = mb_encode_numericentity($item, array(0x80, 0xffff, 0, 0xffff), 'UTF-8');
        }
    });
    return mb_decode_numericentity(json_encode($data), array(0x80, 0xffff, 0, 0xffff), 'UTF-8');
}

function dataEncode($str, $expire = null)
{
    $key = config('hash-key');
    $time = '';
    if ($expire) {
        $time = strval(time() + $expire);
        $key .= $time;
    }
    $data = urlsafeBase64Encode($str);
    $hash = urlsafeBase64Encode(hash_hmac('sha1', $data, $key, true));
    $result = [$data, $hash];
    if ($expire) {
        $result[] = $time;
    }
    return implode('.', $result);
}

function dataDecode($str)
{
    $pairs = explode('.', $str);
    if (count($pairs) < 2) {
        return null;
    }
    $data = $pairs[0];
    $sign = $pairs[1];
    $time = isset($pairs[2]) ? $pairs[2] : '';
    $key = config('hash-key') . $time;
    $beSign = urlsafeBase64Encode(hash_hmac('sha1', $data, $key, true));
    if ($sign !== $beSign) {
        return null;
    }
    if ($time && $time < time()) {
        return null;
    }
    return urlsafeBase64Decode($data);
}

/**
 * @param string $input Anything really
 *
 * @return string The base64 encode of what you passed in
 */
function urlsafeBase64Encode($input)
{
    return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
}

/**
 * @param string $input A base64 encoded string
 *
 * @return string A decoded string
 */
function urlsafeBase64Decode($input)
{
    $remainder = strlen($input) % 4;
    if ($remainder) {
        $input .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($input, '-_', '+/'));
}

function generateRandomKey($length = 32)
{
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length));
    }
    if (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }
    if (@file_exists('/dev/urandom')) { // Get 100 bytes of random data
        return bin2hex(file_get_contents('/dev/urandom', false, null, 0, $length));
    }
    // Last resort which you probably should just get rid of:
    $randomData = mt_rand() . mt_rand() . mt_rand() . mt_rand() . microtime(true) . uniqid(mt_rand(), true);

    return substr(hash('sha512', $randomData), 0, $length * 2);
}
