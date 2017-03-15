<?php

namespace OCA\SingleSignOn;

/**
 * Class WebDavAuthInfo
 * @author Dauba
 */
class WebDavAuthInfo implements IWebDavAuthInfo
{
    /**
     * requeir keys for auth info
     *
     * @var array
     */
    private static $requireKeys = array("userid", "password");

    /**
     * auth info
     *
     * @var array
     */
    private static $info = array();

    /**
     * Getter for Info
     *
     * @return array
     */
    public static function get($userID, $password)
    {
        self::$info["userid"] = $userID;
        self::$info["password"] = $password;

        foreach (self::$requireKeys as $key) {
            if(!array_key_exists($key, self::$info)) {
                return null;
            }
        }
        return self::$info;
    }
}
