<?php


class SugarPortal_Login_Widget extends WP_Widget {

	/*	Widget setup	*/
	function __construct() {
		//	widget settings
		$widget_ops = array( 'classname' => 'sp-login-widget', 'description' => 'A sidebar login widget.');
		$control_ops = array( 'width' => 200, 'height' => 350, 'id_base' => 'sp-login-widget' );

		//	Create the widget
		$this->WP_Widget( 'sp-login-widget', 'SugarPortal Login', $widget_ops, $control_ops );
	}

	/*	display the widget on the screen.	*/
	function widget( $args, $instance ) {
		if(isset($before_widget)) echo $before_widget;
        $html = "<aside class='widget'>";
        if(!is_user_logged_in()) {
            //  show login form
            $html .= "<p class='widget-title'>User Login</p>";
            $html .= "<div style='text-align:center;'>";
            $html .= wp_login_form(array('echo'=>0));
            $html .= "</div>";
        }
        else {
            //  show user info & logout link
            global $current_user;
            get_currentuserinfo();
            $html .= "<p class='widget-title'>".$instance['widget_title']."</p>";
            $html .= "<div style='text-align:center;'>";
            $html .= "<p>Logged in as <b>".$current_user->display_name."</b></p>";
            //  logout link
            $html .= "<p><a href='".wp_logout_url(home_url())."'>Logout</a></p>";
            $html .= "</div>";
        }
        $html .= "</aside>";
        echo $html;
		if(isset($after_widget)) echo $after_widget;
	}

	/*	Update the widget settings.	*/
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['widget_title'] = strip_tags($new_instance['widget_title'] );
		return $instance;
	}

	/*	Displays the widget settings controls on the widget panel.	 */
	function form( $instance ) {
		/* Set up some default widget settings. */
		$defaults = array( 'widget_title' => 'User Information', 'form_text' => 'Please fill out my form!');
		$instance = wp_parse_args( (array) $instance, $defaults );
        $title_field = $this->get_field_name('widget_title');
        $html = "<label for='".$title_field."'>Title</label><br/>";
        $html .= "<input name='".$title_field."' value='".$instance['widget_title']."'/>";
        
        echo $html;
	}
}


	/*	WIDGETS	*/
	add_action( 'widgets_init', 'sp_login_widget' );
	
	/*	login Widget	*/
	function sp_login_widget() {
		register_widget( 'SugarPortal_Login_Widget' );
	}
?>
