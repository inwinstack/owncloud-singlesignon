<?php
namespace OCA\SingleSignOn;

use Exception;

class SingleSignOnProcessor {

    /**
     * required keys in config/config.php
     */
    private static $requiredKeys = array("sso_login_url",
                                        "sso_auth_method",
                                        "sso_return_url_key",
                                        "sso_requests",
                                        "sso_portal_url",
                                        "sso_global_logout");

    /**
     * \OC\SystemConfig
     */
    private $config;

    /**
     * \OC\Appframework\Http\Request
     */
    private $request; 

    /**
     * user token
     */
    private $token;

    /**
     * user ip
     */
    private $userIp;

    /**
     * url where to redirect after SSO login
     */
    private $redirectUrl;

    /**
     * soap client object
     */
    private $soapClient;

    /**
     * SSO auth method
     */ 
    private $authMathod;
    
    public function run() {
        try {
            $this->process();
        }catch (Exception $e){
            echo $e->getMessage();
        }
    }

    public function __construct() {
        $this->config = \OC::$server->getSystemConfig();
        $this->authMathod = $this->config->getValue("sso_auth_method");

        if ($this->authMathod === "param") {
            array_push(self::$requiredKeys, "sso_url_token_key");
        }
        else {
            array_push(self::$requiredKeys, "sso_cookie_token_key");
        }

        self::checkKeyExist(self::$requiredKeys);
        self::checkConfigValueEmpty(self::$requiredKeys);

        $this->request = \OC::$server->getRequest();
        $this->userIp = $this->request->getRemoteAddress();
        $this->redirectUrl = \OC_Util::getDefaultPageUrl();
        $this->token = $this->request->offsetGet($this->config->getValue("sso_url_token_key")) | \OC::$server->getSession()->get("sso_token") | $this->request->getCookie($this->config->getValue("sso_cookie_token_key"));

        RequestManager::init("soap", $this->config->getValue("sso_portal_url"), $this->config->getValue("sso_requests"));
    }

    public function process() {
        $ssoUrl = $this->config->getValue("sso_login_url");
        $userInfo = RequestManager::getRequest(ISingleSignOnRequest::INFO);

        if(isset($_GET["logout"]) && $_GET["logout"] == "true") {
            if($this->config->getValue("sso_global_logout")) {
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
            header("Status: " . \OCP\AppFramework\Http::STATUS_UNAUTHORIZED); header("WWW-Authenticate: "); header("Retry-After: 120");

            $template = new \OC_Template("singlesignon", "tokenExpired", "guest");
            $template->printPage();
            die();
        }

        if($this->getToken() === false || !RequestManager::send(ISingleSignOnRequest::VALIDTOKEN, array("token" => $this->getToken(), "userIp" => $this->getUserIp()))) {
            $url = ($this->redirectUrl) ? $ssoUrl . $this->config->getValue("sso_return_url_key") . $this->redirectUrl : $ssoUrl;
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
            Util::redirect($this->redirectUrl);
        }
        else {
            Util::login($userInfo->getUserId(), $this->getToken());
            Util::redirect($this->redirectUrl);
        }
    }

    /**
     * Check key is exist or not in config/config.php
     *
     * @param array reqiured keys
     * @return void
     */
    public static function checkKeyExist($requiredKeys) {
        $configKeys = \OC::$server->getSystemConfig()->getKeys();

        foreach ($requiredKeys as $key) {
            if (!in_array($key, $configKeys)) {
                throw new Exception("The config key " . $key . " did't exist.");
            }
        }
    }

    /**
     * Check config value is empty or not.
     *
     * @param array reqiured keys
     * @return void
     **/
    public static function checkConfigValueEmpty($requiredKeys) {
        $config = \OC::$server->getSystemConfig();

        foreach ($requiredKeys as $key) {
            if (!$config->getValue($key)) {
                throw new Exception("The config value " . $key . " is empty.");
            }
        }
    }

    /**
     * Get SingleSignOnProcessor.
     *
     * @return Object \OCA\SingleSigoOnProcessor
     * @author Dauba
     */
    public static function getInstance() {
        return new static();
    }

    /**
     * Get the user token
     *
     * @return string user token
     * @author Dauba
     */
    public function getToken() {
        return $this->token;
    }
    
    /**
     * Get the user ip
     *
     * @return string user ip
     * @author Dauba
     */
    public function getUserIp() {
        return $this->userIp;
    }
}
