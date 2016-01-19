<?php
namespace OCA\SingleSignOn;

use Exception;

class SingleSignOnProcessor {

    private $token;
    private $ssoconfig;
    private $userIp;
    private $redirectUrl;
    private $soapClient;
    private $hostIp;
    private $hostDomainName;
    private $request;

    public function run() {
        try {
            $this->process();
        }catch (Exception $e){
            echo $e->getMessage();
        }
    }

    public function __construct() {
        $this->request = \OC::$server->getRequest();
        $this->ssoconfig = \OC::$server->getSystemConfig()->getValue("SSOCONFIG");
        $this->userIp = $this->request->getRemoteAddress();
        $this->redirectUrl = $this->request->getServerProtocol() . "://" . $this->request->getServerHost() . $this->request->getRequestUri();
        $this->token = $this->request->offsetGet($this->ssoconfig["urlToken"]) | \OC::$server->getSession()->get("sso_token") | $this->request->getCookie($this->ssoconfig["token"]);
        RequestManager::init("soap", $this->ssoconfig["singleSignOnServer"], $this->ssoconfig["requests"]);
    }

    public function process() {
        $ssoUrl = $this->ssoconfig["ssoUrl"] . $this->ssoconfig["redirectUrl"] . $redirectUrl;
        $userInfo = RequestManager::getRequest(ISingleSignOnRequest::INFO);

        if(isset($_GET["logout"]) && $_GET["logout"] == "true") {
            if($this->ssoconfig["logoutSSO"]) {
                RequestManager::send(ISingleSignOnRequest::INVALIDTOKEN);
            }
            \OC_User::logout();
            Util::redirect($ssoUrl);
        }

        if(empty($ssoUrl)) {
            header("HTTP/1.1 " . \OCP\AppFramework\Http::STATUS_UNAUTHORIZED);
            header("Status: " . \OCP\AppFramework\Http::STATUS_UNAUTHORIZED);
            header("WWW-Authenticate: ");
            header("Retry-After: 120");

            $template = new \OC_Template("singlesignon", "verificationFailure", "guest");
            $template->printPage();
            die();
        }

        if(\OC_User::isLoggedIn() && ($this->token === false || !RequestManager::send(ISingleSignOnRequest::VALIDTOKEN, array("token" => $this->getToken(), "userIp" => $this->getUserIp())))) {
            header("HTTP/1.1 " . \OCP\AppFramework\Http::STATUS_UNAUTHORIZED);
            header("Status: " . \OCP\AppFramework\Http::STATUS_UNAUTHORIZED);
            header("WWW-Authenticate: ");
            header("Retry-After: 120");

            $template = new \OC_Template("singlesignon", "tokenExpired", "guest");
            $template->printPage();
            die();
        }

        if($this->getToken() === false || !RequestManager::send(ISingleSignOnRequest::VALIDTOKEN, array("token" => $this->getToken(), "userIp" => $this->getUserIp()))) {
            $url = ($redirectUrl === false) ? $ssoUrl : $ssoUrl . $this->ssoconfig["returnUrl"] . $redirectUrl;
            Util::redirect($url);
        }

        if(\OC_User::isLoggedIn()) {
            return ;
        }

        if(!$userInfo->send(array("token" => $this->getToken(), "userIp" => $this->getUserIp()))) {
            return ;
        }

        if(!\OC_User::userExists($userInfo->getUserId())) {
            Util::firstLogin($userInfo, $this->getToken());
            Util::redirect($redirectUrl);
        }
        else {
            Util::login($userInfo->getUserId(), $this->getToken());
            Util::redirect($redirectUrl);
        }
    }

    public static function getInstance() {
        return new static();
    }

    public function getToken() {
        return $this->token;
    }
    
    public function getUserIp() {
        return $this->userIp;
    }
}
