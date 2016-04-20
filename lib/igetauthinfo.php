<?php

namespace OCA\SingleSignOn;

/**
 * Interface IGetAuthInfo
 * @author Dauba
 */
interface IGetAuthInfo
{
    /**
     * set auth info
     *
     * @return void
     * @author Dauba
     */
    public function setInfo();
    
    /**
     * get auth info
     *
     * @return array
     * @author Dauba
     */
    public function getInfo();                                                                                                              
}
