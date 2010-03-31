<?php
/**
 * @author markus
 * $Id: GetEnv.php 6 2009-11-18 14:52:50Z markus $
 */
class GetEnv {
    /**
     * Get an environment variable with all the REDIRECT_ prefixes stripped off
     */
    public static function getEnvVar($var)
    {
        // Find out how deep the redirect goes
        reset($_SERVER);
        $key = key($_SERVER);
        $redirectLevel = substr_count($key, 'REDIRECT_');

        $result = '';
        $prefix = '';
        for ($i = 0; $i < $redirectLevel + 1; $i++) {
                if (isset($_SERVER[$prefix . $var])) {
                        $result = $_SERVER[$prefix . $var];
                }
                $prefix .= 'REDIRECT_';
        }
        return $result;
    }
}

?>