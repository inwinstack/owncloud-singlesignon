<?php
namespace OCA\SingleSignOn;

interface IUserInfoRequest extends ISingleSignOnRequest {
    public function getUserId();
    public function getEmail();
    public function getGroups();
    public function getDisplayName();
}
