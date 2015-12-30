<?php
namespace OCA\SingleSignOn;

use Exception;

class RequestManager {
    private static $soapClient;
    private static $requests = array();

    public static function init($clientType, $serverUrl, $requests) {
        self::$soapClient = new \SoapClient(NULL, array("location" => $serverUrl . "server.php", "uri" => $serverUrl));

        foreach($requests as $request) {
            if(!class_exists($request)) {
                throw new Exception("The class " . $request . " did't exist.");
            }
        }

        foreach($requests as $request) {
            $request = new $request(self::$soapClient);
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
