<?php
    global $sugar_icon;
    $sugar_url = get_option('sugar_url');
    $sugar_user = get_option('sugar_user');
    $sugar_pwd = get_option('sugar_pwd');
?>
<img src="<?php echo $sugar_icon; ?>" style="float: left;margin-top: 15px; margin-right: 5px;"/><h2>SugarCRM Settings</h2>
<hr/>
<?php if(isset($_GET["settings-updated"])) { ?>
    <div id="message" class="updated">
        <p><strong><?php _e('Settings saved.') ?></strong></p>
    </div>
    <?php }
    if(isset($_POST["refresh_meta"])) {
        sugarpress_refresh_metadata();sugarpress_refresh_users();?>
    <div id="message" class="updated">
        <p><strong>Metadata Refreshed</strong></p>
    </div>
    <?php
    }
    ?>
<p>Connection Status:
    <?php echo (sugarpress_status() ? "<span style='font-weight:bold;color:green'>Connected</span>":"<span style='font-weight:bold;color:red'>Not Connected</span>");?>
    </p>
<hr/>
<p style="font-weight: bold">Use the settings below to connect WordPress to SugarCRM:</p>
<form method="post" action="options.php">
    <?php settings_fields('sugarpress_settings');  ?>
    <div>
        <label>SugarCRM REST Endpoint:</label><br/>
        <input type="text" name="sugar_url" placeholder="http://sugar-server/service/v4_1/rest.php" size="50" value="<?php echo $sugar_url; ?>"/><br/>
        <label>Sugar User ID:</label><br/>
        <input type="text" name="sugar_user" value="<?php echo $sugar_user; ?>" /><br/>
        <label>Sugar User Password:</label><br/>
        <input type="password" name="sugar_pwd" value="<?php echo $sugar_pwd; ?>"/>
    </div>
    <input type="submit" value="Save"/>
</form>
<hr/>
<p>Refresh Module and User Lists</p>
<form method="POST">
<input type="hidden" name="refresh_meta" value="1"/>
<input type="submit" value="Refresh"/>
</form>
<?php
    //  test our credentials
    //$api = new SugarREST($sugar_url,$sugar_user,$sugar_pwd,true);
    
    //$api->getModules(true);
?>
