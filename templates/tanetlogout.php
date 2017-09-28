<?php
style("singlesignon", "styles");
?>
<ul>
    <li class='update'>
        <?php p($l->t('Logout success.')); ?><br/><br/>
        <a class="button" href="<?php echo rtrim(\OC_Config::getValue("storage_url"),'/') . "/tanetlogin.php" ?>"><?php p($l->t("Login again.")) ?></a>
    </li>
</ul>

