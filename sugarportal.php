<?php global $sugar_icon; ?>
<img src="<?php echo $sugar_icon; ?>" style="float: left;margin-top: 15px; margin-right: 5px;"/><h2>SugarPortal Settings</h2>
<hr/>
<?php if(isset($_GET["settings-updated"])) { ?>
    <div id="message" class="updated">
        <p><strong><?php _e('Settings saved.') ?></strong></p>
    </div><?php } ?>
<p>Use the settings below to configure SugarPortal:</p>
<form method="post" action="options.php">
    <?php settings_fields('sugarportal_settings'); ?>
    <table>
        <tr>
            <td><label>Enable SugarCRM Portal Login:</label></td>
            <td><input type="checkbox" name="sp_enable_login" value="1" <?php checked(1, get_option('sp_enable_login'), true) ?> ></input></td>
        </tr>
        <tr>
            <td><label>User Login Module:</label></td>
            <td><?php echo module_dropdown('sp_login_module',get_option('sp_login_module')); ?></td>
        </tr>
        <tr>
            <td><label>User ID Field:</label></td>
            <td><input type="text" name="sp_login_id" value="<?php echo get_option('sp_login_id'); ?>"/></td>
        </tr>
        <tr>
            <td><label>User Password Field:</label></td>
            <td><input type="text" name="sp_login_pwd" value="<?php echo get_option('sp_login_pwd'); ?>" /></td>
        </tr>
        <tr>
            <td><label>User Email Field:</label></td>
            <td><input type="text" name="sp_login_email" value="<?php echo get_option('sp_login_email'); ?>" /></td>
        </tr>
        <tr>
            <td><label>User First Name Field:</label></td>
            <td><input type="text" name="sp_login_fname" value="<?php echo get_option('sp_login_fname'); ?>" /></td>
        </tr>
        <tr>
            <td><label>User Last Name Field:</label></td>
            <td><input type="text" name="sp_login_lname" value="<?php echo get_option('sp_login_lname'); ?>" /></td>
        </tr>
        <tr>
            <td><label>Additional Sync Field:</label></td>
            <td><input type="text" name="sp_sync_field1" value="<?php echo get_option('sp_sync_field1'); ?>" /></td>
        </tr>
        <tr>
            <td><label>Login Landing Page:</label></td>
            <td>
            <?php
                //  show dropdown list of pages
                $sp_landing_page = get_option('sp_landing_page');
                $selected = $sp_landing_page;
                $args = array('name'=>'sp_landing_page','show_option_none'=>'-- None --','option_none_value'=>0,'selected'=>$selected,'post_status'=>'draft,publish,pending');
                wp_dropdown_pages($args); ?>
            </td>
        </tr>
        <tr>
            <td style="vertical-align:top;"><label>Protected Pages:</label></td>
            <td>
                <?php
                    $protected = get_option('sp_protected_pages');
                    wp_multiselect_pages(array('name'=>'sp_protected_pages', 'selected'=>$protected));?>
            </td>
        </tr>
        <tr>
            <td><label>Disable WordPress Login:</label></td>
            <td>
            <input type="checkbox" name="sp_disable_wp_login" value="1" <?php checked(1, get_option('sp_disable_wp_login'), true) ?> ></input> (<b>WARNING!!!</b> Checking this option will prevent anyone not in SugarCRM from logging in - including admins!)</td>
        </tr>
    </table>
    <input type="submit" value="Save"/>
</form>
<?php
    //  test login function
    //require('lib/functions.php');
    
    //sugarportal_login('joe','football');
?>
