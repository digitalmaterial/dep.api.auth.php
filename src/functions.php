<?php

/**
 * Get the default User-Agent string to use with Guzzle
 *
 * @return string
 */
if (!function_exists('default_user_agent')) {
    function default_user_agent()
    {
        static $defaultAgent = '';

        if (!$defaultAgent) {
            $defaultAgent = 'MTNDEP/' . MTNDEP\DEPClient::VERSION;
            if (extension_loaded('curl') && function_exists('curl_version')) {
                $defaultAgent .= ' curl/' . \curl_version()['version'];
            }
            $defaultAgent .= ' PHP/' . PHP_VERSION;
        }

        return $defaultAgent;
    }
}

/**
 * If pecl http_build_url function is not available then add our own function.
 *
 * @return string
 */
if (!function_exists('http_build_url')) {
    function http_build_url(array $parts)
    {
        return (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') .
            ((isset($parts['user']) || isset($parts['host'])) ? '//' : '') .
            (isset($parts['user']) ? "{$parts['user']}" : '') .
            (isset($parts['pass']) ? ":{$parts['pass']}" : '') .
            (isset($parts['user']) ? '@' : '') .
            (isset($parts['host']) ? "{$parts['host']}" : '') .
            (isset($parts['port']) ? ":{$parts['port']}" : '') .
            (isset($parts['path']) ? "{$parts['path']}" : '') .
            (isset($parts['query']) ? "?{$parts['query']}" : '') .
            (isset($parts['fragment']) ? "#{$parts['fragment']}" : '');
    }
}