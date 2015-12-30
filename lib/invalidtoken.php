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
        setcookie("token", "", time() - 3600);
    }

    public function getErrorMsg() {}
}
