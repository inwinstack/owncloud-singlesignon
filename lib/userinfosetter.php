<?php

namespace OCA\SingleSignOn;

/**
 * Class UserInfoSetter
 * @author Dauba
 */
class UserInfoSetter
{
    /**
     * Set ownCloud user info
     *
     * @return void
     */
    public static function setInfo($user, $userInfo)
    {
        $config = \OC::$server->getConfig();
        $userID = $userInfo->getUserId();

        $regionData = \OC::$server->getConfig()->getUserValue($userID, "settings", "regionData",false);
        $regionDataDecoded = json_decode($regionData,true);
        if (!$regionData ||
            $regionDataDecoded['region'] !== $userInfo->getRegion() ||
            $regionDataDecoded['schoolCode'] !== $userInfo->getSchoolId()
            ){
                $data = ['region' => $userInfo->getRegion(),
                         'schoolCode' => $userInfo->getSchoolId(),
                        ];
                $config->setUserValue($userID, "settings", "regionData", json_encode($data));
        }

        $savedRole = $config->getUserValue($userID, "settings", "role",NULL);
        if ($savedRole !== $userInfo->getRole()) {
            $config->setUserValue($userID, "settings", "role", $userInfo->getRole());
        }
    
        $savedEmail = $config->getUserValue($userID, "settings", "email",NULL);
        if ($savedEmail !== $userInfo->getEmail()) {
            $config->setUserValue($userID, "settings", "email", $userInfo->getEmail());
        }

        $advanceGroup = \OC::$server->getSystemConfig()->getValue("sso_advance_user_group", NULL);

        \OC_User::setDisplayName($userID, $userInfo->getDisplayName());

        if ($userInfo->getRole() === $advanceGroup) {
            //$config->setUserValue($userID, "files", "quota", "15 GB");
            //if($config->getUserValue($userID, "teacher_notification", "notification", NULL) === NULL) {
            //    $config->setUserValue($userID, "teacher_notification", "notification", "1");
            //}

            $group = \OC::$server->getGroupManager()->get($advanceGroup);
            if(!$group) {
                $group = \OC::$server->getGroupManager()->createGroup($advanceGroup);
            }
            $group->addUser($user);
        }
        //$config->setUserValue($userID, "files", "quota", "30 GB");
    }
    
}
