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
        if (!$regionData){
            $data = ['region' => $userInfo->getRegion(),
                    'schoolCode' => 'undefined',
            ];
            $config->setUserValue($userID, "settings", "regionData", json_encode($data));
        }

        $config->setUserValue($userID, "settings", "regionData", json_encode($data));


        if ($config->getUserValue($userID, "settings", "role") != NULL && $config->getUserValue($userID, "files", "quota") == "15 GB") {
            return;
        }

        $advanceGroup = \OC::$server->getSystemConfig()->getValue("sso_advance_user_group", NULL);

        \OC_User::setDisplayName($userID, $userInfo->getDisplayName());
        $config->setUserValue($userID, "settings", "email", $userInfo->getEmail());

        if ($userInfo->getRole() === $advanceGroup) {
            $config->setUserValue($userID, "settings", "role", $userInfo->getRole());
            $config->setUserValue($userID, "files", "quota", "15 GB");
            if($config->getUserValue($userID, "teacher_notification", "notification", NULL) === NULL) {
                $config->setUserValue($userID, "teacher_notification", "notification", "1");
            }

            $group = \OC::$server->getGroupManager()->get($advanceGroup);
            if(!$group) {
                $group = \OC::$server->getGroupManager()->createGroup($advanceGroup);
            }
            $group->addUser($user);
        }
    }
    
}
