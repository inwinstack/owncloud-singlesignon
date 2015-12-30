<?php

namespace OCA\SingleSignOn;

class GetToken1 implements ISingleSignOnRequest {

    private $processor;
    private $errorMsg;
    
    public function __construct($processor){
        $this->processor = $processor;
    }

    public function name() {
        return ISingleSignOnRequest::GETTOKEN;    
    }

    public function send($data) {
        $result = $this->processor->getSoapClient()->__soapCall("getToken1", array(array("UserId" => $data["userId"],"Password" => $data["password"],  "UserIp" => $this->processor->getUserIp())));

        if($result->return->ActXML->StatusCode != 200) {
            $this->errorMsg = $result->return->ActXML->Message;
            return false;
        }

        return $result->return->ActXML->RsInfo->TokenId;
    }

    public function getErrorMsg() {
        return $this->errorMsg;
    }
}
