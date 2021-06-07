<?php
/**
 * Grab config value
 *
 * @param string $config The config key
 * @return string|array The config value
 */
function config(string $config)
{
    $configExplode = explode('.', $config);

    $configFile = $configExplode[0];
    array_shift($configExplode);

    $configValue = include DOC_ROOT . 'Config/' . $configFile . '.php';

    foreach ($configExplode as $value) {
        $configValue = $configValue[$value];
    }

    return $configValue;
}

/**
 * Grab ENV value
 *
 * @param string $env The env key to obtain
 * @param bool $default The default value to return if env not found
 * @return string|bool
 */
function env(string $env, $default = null)
{
    if (getenv($env)) {
        $env = getenv($env);
        if (preg_match('/^(["\']).*\1$/m', $env)) {
            $env = str_replace('"', '', $env);
            $env = str_replace("'", '', $env);
        } else {
            $env = str_replace("\n", '', $env); // remove new lines
            $env = str_replace("\r", '', $env);
        }
        return $env;
    } else {
        if ($default) {
            return $default;
        } else {
            return false;
        }
    }
}

/**
 * Global Translation function
 *
 * @param string $data The file.key pair for translation look up
 * @return  string
 */
function trans($data, ...$args)
{
    $data = explode(".", $data);
    $file = $data[0];
    $var = $data[1];

    if (!empty($_COOKIE[config('app.localization.language_cookie')])) {
        $mode = $_COOKIE[config('app.localization.language_cookie')];
    } else {
        $mode = config('app.localization.default_language');
    }

    $lang = include(DOC_ROOT . "Utilities/Lang/$mode/$file.php");
    if (!empty($lang[$var])) {
        if (count($args) > 0) {
            return sprintf($lang[$var], ...$args);
        } else {
            return $lang[$var];
        }
    } else {
        return '';
    }
}

function session(string $key = null, string $value = null)
{
    // return SessionManager::sessionSetterGetter($key, $value);
    if ($key == null) {
        return $_SESSION;
    }

    if ($value == null) {
        return $_SESSION[$key];
    }

    if ($value != null) {
        $_SESSION[$key] = $value;
        return $_SESSION[$key];
    }
}

/**
 * Global View function
 *
 * @param string|array $view The view relative to View folder, or the data in array
 * @param array $data The data to pass to view
 * @return string
 */
function view($view = null, array $data = [])
{
    if(is_array($view)){
        $data = $view;
        $view = str_replace("Controller", "", Request::$controller) . "/" . Request::$action;
    }

    if($view == null){
        $view = str_replace("Controller", "", Request::$controller) . "/" . Request::$action;
    }

    $class = new \MightyCore\View($view);
    return $class->render($data);
}

/**
 * Return path based on route name
 *
 * @param string $path The path name
 *
 * @return string The requested path
 */
function route($path)
{
    $route = '';
    if (!empty(MightyCore\Routing\RouteStore::$namedRoutes[$path])) {
        $route = MightyCore\Routing\RouteStore::$namedRoutes[$path];
    }
    return $route;
}

function dump(...$args)
{
    if (count($args) == 1) {
        $args = $args[0];
    }

    if(is_object($args)){
        $args = get_object_vars($args);
    }

    echo "<pre>" . print_r($args, true) . "</pre>";
}

function encrypt($plaintext){
    $cipher = 'AES-128-CBC';

    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);

    $ciphertext_raw = openssl_encrypt($plaintext, $cipher, env("APP_KEY"), 0, $iv);
    $hmac = hash_hmac('sha256', $ciphertext_raw, env("APP_KEY"), $as_binary=true);
    $ciphertext = str_replace("/", "_", base64_encode( $iv.$hmac.$ciphertext_raw ));

    return $ciphertext;
}

function decrypt($ciphertext){
    $ciphertext = str_replace("_", "/", $ciphertext);
    $c = base64_decode($ciphertext);
    $cipher = 'AES-128-CBC';

    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = substr($c, 0, $ivlen);

    $sha2len = 32;
    $hmac = substr($c, $ivlen, $sha2len);
    $ciphertext_raw = substr($c, $ivlen+$sha2len);

    $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, env("APP_KEY"), 0, $iv);

    $calcmac = hash_hmac('sha256', $ciphertext_raw, env("APP_KEY"), $as_binary=true);
    if (hash_equals($hmac, $calcmac))// timing attack safe comparison
    {
        return $original_plaintext;
    }else {
        return false;
    }
}

