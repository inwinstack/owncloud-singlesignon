<?php

namespace OCA\SingleSignOn;

/**
 * Interface IRequestManager
 * @author Dauba
 */
interface IRequestManager
{
    /**
     * init request manager
     * check request exist or not and register all requests
     *
     * @param string $serverUrl single sign on API portal url
     * @param IRequest array $request
     * @return void
     * @author Dauba
     **/
    public static function init($serverUrl, $requests);

    /**
     * send request
     * @param string $requestName
     * @param array $data data that you want to send
     * @return void
     * @author Dauba
     **/
    public static function send($requestName, $data = array());

    /**
     * getRequest
     *
     * @param string $requestName 
     * @return IRequest
     * @author Dauba
     **/
    public static function getRequest($requestName);
}
