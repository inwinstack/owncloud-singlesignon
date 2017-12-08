<?php
style("singlesignon", "styles");
?>
<ul>
    <li class='update'>
        <?php p($l->t('Logout success.')); ?><br/><br/>
        <?php if ($_['login_type'] == 'tanet'):?>
            <a class="button" href="<?php echo rtrim(\OC_Config::getValue("storage_url"),'/') . "/tanetlogin.php"?>"><?php p($l->t("Login again.")) ?></a>
        <?php elseif (($_['login_type'] == 'asus')):?>
            <a class="button" href="<?php echo rtrim(\OC_Config::getValue("storage_url"),'/') . "/login.php"?>"><?php p($l->t("Login again.")) ?></a>
        <?php elseif (($_['login_type'] == 'sso')):?>
            <a class="button hidden" href="<?php echo \OC_Config::getValue("sso_login_url") . \OC_Config::getValue("sso_return_url_key"). "/" ?>"><?php p($l->t("Login again.")) ?></a>
        <?php elseif (($_['login_type'] == 'local')):?>
            <a class="button" href="<?php echo "/index.php/local" ?>"><?php p($l->t("Login again.")) ?></a>
         <?php else: ?>
            <a class="button" href="<?php echo \OC_Config::getValue("sso_login_url") . \OC_Config::getValue("sso_return_url_key") . "/" ?>"><?php p($l->t("Login again.")) ?></a>
         <?php endif; ?>
    </li>
</ul>

