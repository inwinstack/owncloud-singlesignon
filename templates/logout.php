<?php
style("singlesignon", "styles");
?>
<ul>
    <li class='update'>
        <?php p($l->t('Logout success.')); ?><br/><br/>
        <a class="button hidden" href="<?php echo \OC_Config::getValue("sso_login_url") . \OC_Config::getValue("sso_return_url_key") . "/" ?>"><?php p($l->t("Login again.")) ?></a>
    </li>
</ul>
