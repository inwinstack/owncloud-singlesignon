<?php

namespace OCA\SingleSignOn;

/**
 * Interface IWebDavAuthInfo
 * @author Dauba
 */
interface IWebDavAuthInfo
{
    /**
     * set auth info
     *
     * @param string $userID
     * @param string $password
     * @return void
     * @author Dauba
     */
    public static function init($userID, $password);

    /**
     * get auth info
     *
     * @return array
     * @author Dauba
     */
    public static function get();                                                                                                              
}
