<?php
namespace OCA\SingleSignOn;

class Util {
    public static function login($userInfo, $authInfo) {
        $userID = $userInfo->getUserId();
        $manager = \OC::$server->getUserManager();
        $manager->emit('\OC\User', 'preLogin', array($userID, $token));

        $user = $manager->get($userID);
        \OC::$server->getUserSession()->setUser($user);
        \OC::$server->getUserSession()->setLoginName($user);
        \OC_Util::setupFS($userID);
        \OC::$server->getUserFolder($userID);

        $manager->emit('\OC\User', 'postLogin', array($userID, $token));

        self::wirteAuthInfoToSession($authInfo);

        return true;
    }

    public static function firstLogin($userInfo, $authInfo) {
        $userID = $userInfo->getUserId();
        $password = RequestManager::getRequest(ISingleSignOnRequest::USERPASSWORDGENERATOR) ? RequestManager::send(ISingleSignOnRequest::USERPASSWORDGENERATOR) : $userID;

        \OC_User::createUser($userID, $password);
        \OC_User::setDisplayName($userID, $userInfo->getDisplayName());
        \OC::$server->getConfig()->setUserValue($userID, "settings", "email", $userInfo->getEmail());
        self::wirteAuthInfoToSession($authInfo);
        return \OC_User::login($userID, $password);
    }

    public static function webDavLogin($userID, $password) {
        $data["userId"] = $userID;
        $data["password"] = $password;
        $data["userIp"] = \OC::$server->getRequest()->getRemoteAddress();

        $config = \OC::$server->getSystemConfig();
        RequestManager::init($config->getValue("sso_portal_url"), $config->getValue("sso_requests"));

        $token = RequestManager::send(ISingleSignOnRequest::GETTOKEN, $data);

        $userInfo = RequestManager::getRequest(ISingleSignOnRequest::INFO);

        if(!$userInfo->send(array("token1" => $token, "userIp" => $data["userIp"]))) {
            return ;
        }

        if($config->getValue("sso_multiple_region")) {
            self::redirectRegion($userInfo, $config->getValue("sso_regions"), $config->getValue("sso_owncloud_url"), $token);
        }
        
        if(!\OC_User::userExists($userInfo->getUserId())) {
            return self::firstLogin($userInfo, $token);
        }

        if($token){
            return self::login($userInfo, $token);
        }

        return false;
    }

    public static function redirect($url) {
        if(!$url) {
            \OC_Util::redirectToDefaultPage();
        }
        else {
            header("location: " . $url);
            exit();
        }
    }

    /**
     * Check user region and redirect to correct region.
     *
     * @return void
     */
    public static function redirectRegion($userInfo, $regions, $serverUrls, $token) {
        $region = $userInfo->getRegion();
        $request = \OC::$server->getRequest();

        if($request->getServerHost() === $serverUrls[$regions[$region]]) {
            return ;
        }

        $redirectUrl = $request->getServerProtocol() . "://" .$serverUrls[$regions[$region]] . $request->getRequestUri();

        self::redirect($redirectUrl);
    }

    /**
     * Write auth info to session
     *
     * @param array $authInfo
     * @return void
     */
    public static function wirteAuthInfoToSession($authInfo)
    {
        foreach ($authInfo as $key => $value) {
            \OC::$server->getSession()->set("sso_" . $key, $value);
        }
    }
}
