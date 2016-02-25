<?php

namespace OCA\SingleSignOn;

class InvalidToken implements ISingleSignOnRequest {
    private $soapClient;

    public function __construct($soapClient){
        $this->soapClient = $soapClient;
    }
 
    public function name() {
        return ISingleSignOnRequest::INVALIDTOKEN;
    }

    public function send($data = null) {
        $result = $this->soapClient->__soapCall("invalidToken1", array(array('TokenId' => $data["token"])));
    }

    public function getErrorMsg() {}
}
