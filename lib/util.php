<?php
namespace OCA\SingleSignOn;

use Exception;


class Util {

    //'{'openid':['xx國中','教師','openid',5,1],'openid':['xx國小','教師','openid',6,2],}'
    public static $teachersArray =
        '{
        "openid":["xx國中","教師","openid",5,1]
        }';

    public static function login($userInfo, $authInfo) {
        $userID = $userInfo->getUserId();
        $userToken = $userInfo->getToken();
        $manager = \OC::$server->getUserManager();

        //$manager->emit('\OC\User', 'preLogin', array($userID, $userToken));

        $user = $manager->get($userID);
        \OC::$server->getUserSession()->setUser($user);
        \OC::$server->getUserSession()->setLoginName($user);
        \OC_Util::setupFS($userID);
        \OC::$server->getUserFolder($userID);

        if (class_exists('\\OCA\\SingleSignOn\\UserInfoSetter')) {
            UserInfoSetter::setInfo($user, $userInfo);
        }

        $manager->emit('\OC\User', 'postLogin', array($user, $userToken));

        self::wirteAuthInfoToSession($authInfo);

        if ($userInfo->getRole() === 'teacher'){
            if (array_key_exists($userInfo->openID,json_decode(Util::$teachersArray,true))){
                $teacherInfo = json_decode(Util::$teachersArray,true)[$userInfo->openID];

                $schoolName = $teacherInfo[0];
                $classYear = $teacherInfo[3];
                $classNum = $teacherInfo[4];
                $group = $schoolName . '_' . $classYear . '_' . $classNum;

                self::addToGroup($userID, $group);

                if(!\OC_SubAdmin::isSubAdminofGroup($userID, $group)) {
                    \OC_SubAdmin::createSubAdmin($userID, $group);
                }

            }
        }
        else if ($userInfo->getRole() === 'student'){
            $result = self::callSchoolInfoAPI($userInfo->openID);

            if ($result){
                $schoolName = $result['schoolName'];
                $classYear =  $result['classYear'];
                $className =  $result['className'];
                $group = $schoolName . '_' . $classYear . '_' . $className;

                self::addUserToGroup($userID, $group);

            }
        }
        return true;
    }
    //'https:\/\/openid.tn.edu.tw\/op\/user.aspx\/St3580599'
    public static function filterOpenIDandCountry($openID){
        if (preg_match('/tn\.edu\.tw/',$openID)){
            $pieces = explode("https://openid.tn.edu.tw/op/user.aspx/", $openID);
            return array('country' => 'tainan', 'openid' => $pieces[1]);
        }
        return array();
    }

    public static function getcountryURLAPI($countryName){
        switch ($countryName){
            case 'tainan':
                return 'https://odata.tn.edu.tw/ebookapi/api/getByEduClassDrive?id=';
            default:
                return '';
        }
    }

    public static function callSchoolInfoAPI($openID){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $filterOpenIDInfo = self::filterOpenIDandCountry($openID);

        if (empty($filterOpenIDInfo)){
            return false;
        }

        $country = $filterOpenIDInfo['country'];

        $apiURL = self::getcountryURLAPI($country);
        curl_setopt($ch, CURLOPT_URL, $apiURL.$filterOpenIDInfo['openid']);

        $result = curl_exec($ch);
        if (!curl_errno($ch)) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code == 200){
                $result = self::filterSchoolInfo($country, $result);
            }
            else{
                $result = false;
            }
        }
        curl_close($ch);
        return $result;
    }

    public static function filterSchoolInfo($countryName,$rawSchoolInfo){
        switch ($countryName){
            case 'tainan':
                $schoolName = json_decode($rawSchoolInfo,true)[0]['schoolName'];
                $classYear = json_decode($rawSchoolInfo,true)[0]['classYear'];
                $className = json_decode($rawSchoolInfo,true)[0]['className'];

                if($schoolName !== null and $classYear !== null and $className !== null){

                    if (preg_match("/^([\x7f-\xff]+)(國中|高中)$/", $schoolName)) {
                        $classYear+=6;
                    }
                }

                return array('schoolName' => $schoolName,'classYear'=>$classYear,'className'=>$className);
            default:
                return false;
        }
    }

    public static function addUserToGroup($userID,$groupName) {
        if(!\OC_Group::groupExists($groupName)) {
            \OC_Group::createGroup($groupName);
        }

        if( !\OC_Group::inGroup( $userID, $groupName )) {
            $success = \OC_Group::addToGroup( $userID, $groupName );
        }
    }
    public static function firstLogin($userInfo, $authInfo) {
        $userID = $userInfo->getUserId();
        $password = RequestManager::getRequest(ISingleSignOnRequest::USERPASSWORDGENERATOR) ? RequestManager::send(ISingleSignOnRequest::USERPASSWORDGENERATOR) : $userID;

        $user = \OC_User::createUser($userID, $password);
        $config = \OC::$server->getConfig();
        $config->setUserValue($userID, "login", "firstLogin", time());

        if (class_exists('\\OCA\\SingleSignOn\\UserInfoSetter')) {
            UserInfoSetter::setInfo($user, $userInfo);
        }

        self::wirteAuthInfoToSession($authInfo);

        if ($userInfo->getRole() === 'teacher'){
            if (array_key_exists($userInfo->openID,json_decode(Util::$teachersArray,true))){
                $teacherInfo = json_decode(Util::$teachersArray,true)[$userInfo->openID];

                $schoolName = $teacherInfo[0];
                $classYear = $teacherInfo[3];
                $classNum = $teacherInfo[4];
                $group = $schoolName . '_' . $classYear . '_' . $classNum;

                self::addToGroup($userID, $group);

                if(!OC_SubAdmin::isSubAdminofGroup($userID, $group)) {
                    OC_SubAdmin::createSubAdmin($userID, $group);
                }

            }
        }
        else if ($userInfo->getRole() === 'student'){
            $result = self::callSchoolInfoAPI($userInfo->openID);

            if ($result){
                $schoolName = $result['schoolName'];
                $classYear =  $result['classYear'];
                $className =  $result['className'];
                $group = $schoolName . '_' . $classYear . '_' . $className;

                self::addUserToGroup($userID, $group);

            }
        }
        return \OC_User::login($userID, $password);
    }

    public static function webDavLogin($userID, $password) {
        $config = \OC::$server->getSystemConfig();

        RequestManager::init($config->getValue("sso_portal_url"), $config->getValue("sso_requests"));

        $authInfo = WebDavAuthInfo::get($userID, $password);

        $userInfo = RequestManager::getRequest(ISingleSignOnRequest::INFO);

        $userInfo->setup(array("action" => "webDavLogin"));

        if(!$userInfo->send($authInfo)) {
            if($userInfo->hasErrorMsg()) {
                \OCP\Util::writeLog("Single Sign-On", $userInfo->getErrorMsg(), \OCP\Util::ERROR);
            }
            return ;
        }

        if($config->getValue("sso_multiple_region")) {
            self::redirectRegion($userInfo, $config->getValue("sso_regions"), $config->getValue("sso_owncloud_url"));
        }
        
        if(!\OC_User::userExists($userInfo->getUserId())) {
            return self::firstLogin($userInfo, $authInfo);
        }

        if($authInfo){
            return self::login($userInfo, $authInfo);
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
    public static function redirectRegion($userInfo, $regions, $serverUrls) {
        $region = $userInfo->getRegion();
        $request = \OC::$server->getRequest();

        if($request->getServerHost() === $serverUrls[$regions[$region]]) {
            return ;
        }

        $redirectUrl = RedirectRegion::getRegionUrl($region);

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

