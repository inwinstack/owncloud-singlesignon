<?php

namespace OCA\SingleSignOn;

/**
 * Interface IAuthInfo
 * @author Dauba
 */
interface IAuthInfo
{
    /**
     * set auth info
     *
     * @return void
     * @author Dauba
     */
    public static function init();
    
    /**
     * get auth info
     *
     * @return array
     * @author Dauba
     */
    public static function get();                                                                                                              
}
