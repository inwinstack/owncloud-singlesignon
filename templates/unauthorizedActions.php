<?php
style("singlesignon", "styles");
?>
<ul>
    <li class='update'>
        <?php p($l->t('Unauthorized actions')); ?><br/><br/>
        <a class="button" <?php print_unescaped(OC_User::getLogoutAttribute()); ?>><?php p($l->t("Redirect to login page")) ?></a>
    </li>
</ul>
