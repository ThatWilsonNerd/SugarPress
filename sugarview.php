<?php
	global $sp_baseurl, $sp_url, $sp_user, $sp_pwd;
	if($sp_url == null || $sp_url == "") {
		//	 no url specified
		echo "<p><font style='color:red;font-weight:bold'>Error:</font> No SugarCRM URL specified.</p>";
	}
	else {
		$sugar = new SugarApi($sp_url,$sp_user,$sp_pwd);
		$sugar->Login();
		$session_id = $sugar->session_id;
		$frame_url = $sp_baseurl."?module=Home&action=index&MSID=".$session_id;
	?>
	<div id="sp_view">
	<iframe src="<?php echo $frame_url; ?>" scrolling="auto" frameborder="0" width="100%" height="1200"></iframe>
	</div>
<?php }
?>
