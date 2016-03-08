<?php
namespace OCA\SingleSignOn;

interface IUserInfoRequest extends ISingleSignOnRequest {

    /**
     * Getter for UserId
     *
     * @return string
     * @author Dauba
     */
    public function getUserId();

    /**
     * Getter for Email
     *
     * @return string
     * @author Dauba
     */
    public function getEmail();

    /**
     * Getter for group
     *
     * @return void
     * @author Dauba
     */
    public function getGroups();

    /**
     * Getter for display name
     *
     * @return string
     * @author Dauba
     */
    public function getDisplayName();

    /**
     * Getter for region
     *
     * @return string
     * @author Dauba
     */
    public function getRegion();

    /**
     * Check user permission
     *
     * @return bool
     * @author Dauba
     */
    public function hasPermission();
}
