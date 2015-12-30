<?php

namespace OCA\SingleSignOn;

class ValidToken implements ISingleSignOnRequest {

    private $processor;
    private $errorMsg;
    
    public function __construct($soapClient){
        $this->soapClient = $soapClient;
    }

    public function name() {
        return ISingleSignOnRequest::VALIDTOKEN;    
    }

    public function send($data = null) {
        $result = $this->soapClient->__soapCall("getToken2", array(array("TokenId" => $data["token"], "userIp" => $data["userIp"])));

        if($result->return->ActXML->StatusCode != 200) {
            $this->errorMsg = $result->return->ActXML->Message;
            return false;
        }

        return true;
    }

    public function getErrorMsg() {
        return $this->errorMsg;
    }
}

