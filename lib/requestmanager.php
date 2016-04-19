<?php
namespace OCA\SingleSignOn;

use Exception;

class RequestManager implements IRequestManager{
    private static $serverConnection;
    private static $requests = array();

    public static function init($serverUrl, $requests) {
        if (!class_exists('\\OCA\\SingleSignOn\\APIServerConnection')) {
            throw new Exception("The class \\OCA\\SingleSignOn\\APIServerConnection did't exist.");
        }

        self::$serverConnection = new \OCA\SingleSignOn\APIServerConnection($serverUrl);
        self::$serverConnection = self::$serverConnection->getConnection();

        foreach($requests as $request) {
            if(!class_exists($request)) {
                throw new Exception("The class " . $request . " did't exist.");
            }
        }

        foreach($requests as $request) {
            $request = new $request(self::$serverConnection);
            if($request instanceof ISingleSignOnRequest) {
                self::$requests[$request->name()] = $request;
            }
        }

        if(!isset(self::$requests[ISingleSignOnRequest::VALIDTOKEN])) {
            throw new Exception("VaildTokenRequest didn't registered");
        }

        if(!isset(self::$requests[ISingleSignOnRequest::INFO])) {
            throw new Exception("GetInfoRequest didn't registered");
        }

        if(!isset(self::$requests[ISingleSignOnRequest::INVALIDTOKEN])) {
            throw new Exception("InVaildTokenRequest didn't registered");
        }
    }

    public static function send($requestName, $data = array()) {
        if(array_key_exists($requestName, self::$requests)) {
            return self::$requests[$requestName]->send($data);
        }
        return false;
    }

    public static function getRequest($requestName) {
        if(array_key_exists($requestName, self::$requests)) {
            return self::$requests[$requestName];
        }
        return false;
    }
}
