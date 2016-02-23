<?php

namespace OCA\SingleSignOn;

class ValidToken implements ISingleSignOnRequest {

    private $errorMsg;
    
    public function __construct($soapClient){
        $this->soapClient = $soapClient;
    }

    public function name() {
        return ISingleSignOnRequest::VALIDTOKEN;    
    }

    public function send($data = null) {
        $result = $this->soapClient->__soapCall("validToken1", array(array("TokenId" => $data["token"], "UserIP" => $data["userIp"])));

        if($result->return->ActXML->StatusCode != 200) {
            $this->errorMsg = $result->return->ActXML->Message;
            return false;
        }

        return $result->return->ActXML->RsInfo;
    }

    public function getErrorMsg() {
        return $this->errorMsg;
    }
}

