<?php

namespace OCA\SingleSignOn;

/**
 * Class AuthInfo
 * @author Dauba
 */
class AuthInfo implements IAuthInfo
{
    /**
     * requeir keys for auth info
     *
     * @var array
     */
    private static $requireKeys = array("key","userid");

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
    public static function get()
    {
        $request = \OC::$server->getRequest();
        $session = \OC::$server->getSession();
        foreach (self::$requireKeys as $key) {
            if($request->offsetGet($key)) {
                self::$info[$key] = $request->offsetGet($key);
            }
            else if($request->getHeader($key)) {
                self::$info[$key] = $request->getHeader($key);
            }
            else if($session->get("sso_" . $key)) {
                self::$info[$key] = $session->get("sso_" . $key);
            }
        }

        foreach (self::$requireKeys as $key) {
            if(!array_key_exists($key, self::$info)) {
                return null;
            }
        }

        //if(\OC_User::getUser() != self::$info["userid"]) {
        if (!in_array(\OC_User::getUser(),[false,self::$info["userid"]])){
            \OC_User::logout();
        }

        return self::$info;
    }
    
}
