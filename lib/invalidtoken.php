<?php

namespace OCA\SingleSignOn;

class InvalidToken implements ISingleSignOnRequest {
    private $processor;

    public function __construct($soapClient){
        $this->soapClient = $soapClient;
    }
 
    function name() {
        return ISingleSignOnRequest::INVALIDTOKEN;
    }

    function send($data = null) {
        $result = $this->soapClient->__soapCall("invalidToken1", array(array('TokenId' => $data->token)));
    }

    public function getErrorMsg() {}
}
