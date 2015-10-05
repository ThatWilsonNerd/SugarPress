<?php

    //  test connection
    if(!function_exists('sugarpress_status')) {
        function sugarpress_status() {
            
            //  call the api
            $sugar_url = get_option('sugar_url');
            $sugar_user = get_option('sugar_user');
            $sugar_pwd = get_option('sugar_pwd');
            $api = new SugarREST($sugar_url,$sugar_user,$sugar_pwd,false);
            return $api->loggedIn();
        }
    }

    //  override the wordpress authentication function
    if(get_option('sp_enable_login') == 1) {
        add_filter('authenticate','sugarportal_authenticate',10,3);
    }
    if(!function_exists('sugarportal_authenticate')) {
        function sugarportal_authenticate($user, $username, $password) {
            if($username == '' || $password == '') return;
            $sugar_auth = sugarportal_login($username,$password);
            $userobj = new WP_User();

            if($sugar_auth->valid == 1) {
                //  user is authenticated, look up WP user record by login
                $sp_login_id = get_option('sp_login_id');
                $sp_login_pwd = get_option('sp_login_pwd');
                $sp_login_email = get_option('sp_login_email');
                $sp_login_fname = get_option('sp_login_fname');
                $sp_login_lname = get_option('sp_login_lname');
                $sp_sync_field1 = get_option('sp_sync_field1');
                
                $user = $userobj->get_data_by( 'login', $sugar_auth->user->$sp_login_id );
                print_r($user);
                $user = new WP_User($user->ID);
                if( $user->ID == 0 ) {
                    $userdata = array( 'user_email' => $sugar_auth->user->$sp_login_email,
                                'user_login' => $sugar_auth->user->$sp_login_id,
                                'first_name' => $sugar_auth->user->$sp_login_fname,
                                'last_name' => $sugar_auth->user->$sp_login_lname
                                );
                    $new_user_id = wp_insert_user( $userdata ); // A new user has been created
                    //  update user meta (flag user as a 'portal' user for dashboard purposes)
                    update_user_meta( $new_user_id,'sp_portal_user', true );
                    
                    //  additional fields
                    

                    // Load the new user info
                    $user = new WP_User ($new_user_id);
                }
            }
            //  disable built-in user authentication (we can fall back on this if needed)
            if(get_option('sp_disable_wp_login')==1) {
                remove_action('authenticate', 'wp_authenticate_username_password', 20);
            }
            return $user;
        }
    }
    
    //  redirect sugarportal users to custom landing page
    if(!function_exists('sugarportal_landing_redirect')) {
        function sugarportal_landing_redirect($redirect_to, $request, $user) {
            //is there a user to check?
            global $user;
            if ( isset( $user->roles ) && is_array( $user->roles ) ) {
                //check for admins
                if ( in_array( 'administrator', $user->roles ) ) {
                    // redirect them to the default place
                    return $redirect_to;
                } else {
                    //  check for sugarportal user, redirect to landing page if specified
                    if(get_user_meta($user->ID,'sp_portal_user',true) == true) {
                        if(get_option('sp_landing_page') != '' && get_option('sp_landing_page') != 0) {
                            return get_permalink(get_option('sp_landing_page'),false);
                        }
                        else {
                            return home_url();
                        }
                    }
                }
            } else {
                return $redirect_to;
            }
        }
    }
    if(get_option('sugarportal_installed') == true) {
        add_filter('login_redirect','sugarportal_landing_redirect',10,3);
    }
    
    //  query Sugar REST API for contact info
    if(!function_exists('sugarportal_login')) {
        function sugarportal_login($user, $pwd) {
            //require_once($sugarapi);
            $sugar_url = get_option('sugar_url');
            $sugar_user = get_option('sugar_user');
            $sugar_pwd = get_option('sugar_pwd');
            $sp_login_module = get_option('sp_login_module');
            $sp_login_id = get_option('sp_login_id');
            $sp_login_pwd = get_option('sp_login_pwd');
            $sp_login_email = get_option('sp_login_email');
            $sp_login_fname = get_option('sp_login_fname');
            $sp_login_lname = get_option('sp_login_lname');
            $sp_sync_field1 = get_option('sp_sync_field1');
            
            //  build our query
            $request = array(
                "filter" => array(
                    array( "$sp_login_id" => $user )
                ),
                "max_num"=>1,
                "offset"=>0,
                "fields"=>"id,$sp_login_id,$sp_login_pwd,$sp_login_email,$sp_login_fname,$sp_login_lname,$sp_sync_field1",
            );
            
            //  call the api
            $api = new SugarREST($sugar_url,$sugar_user,$sugar_pwd,false);
            $response = $api->filter($sp_login_module, $request);

            //  check the password
            $sugar_auth = new stdClass();
            if(property_exists($response,'records') && count($response->records)>0 && ($pwd == $response->records[0]->$sp_login_pwd)) {
                //  success!
                $sugar_auth->valid = 1;
                $sugar_auth->user = $response->records[0];
            }
            else {
                //  invalid user/pwd combo
                $sugar_auth->valid = 0;
                $sugar_auth->message = "Invalid user/password combination";
            }
            return $sugar_auth;
        }
    }
    
    //  get sugar metadata
    if(!function_exists('sugarpress_refresh_metadata')) {
        function sugarpress_refresh_metadata() {
            $sugar_url = get_option('sugar_url');
            $sugar_user = get_option('sugar_user');
            $sugar_pwd = get_option('sugar_pwd');
            $api = new SugarREST($sugar_url,$sugar_user,$sugar_pwd,false);
            $modules = $api->getModules();
            update_option('sugarpress_metadata',json_encode($modules));
        }
    }
    
    if(!function_exists('module_dropdown')) {
        function module_dropdown($name, $selected = null) {
            if(get_option('sugarpress_metadata') == null) {
                sugarpress_refresh_metadata();
            }
            $modules = json_decode(get_option('sugarpress_metadata'));
            $html = "<select name='$name'>";
            foreach($modules as $key => $value) {
                $html .= "<option value='$key' ".($selected == $key ? "selected": "").">$key</option>";
            }
            $html .= "</select>";
            return $html;
        }
    }
    
    if(!function_exists('module_metadata')) {
        function module_metadata() {
            //  return json string of module data
            return get_option('sugarpress_metadata');
        }
    }
    
    if(!function_exists('sugarpress_refresh_users')) {
        function sugarpress_refresh_users() {
            //  call the api
            $sugar_url = get_option('sugar_url');
            $sugar_user = get_option('sugar_user');
            $sugar_pwd = get_option('sugar_pwd');
            $api = new SugarREST($sugar_url,$sugar_user,$sugar_pwd,false);
            $users = $api->getUsers();
            //print_r($users);
            $user_meta = array();
            foreach($users as $user) {
                $u = new stdClass();
                $u->id =$user->id;
                $u->user_name = $user->user_name;
                $u->full_name = $user->full_name;
                $user_meta[] = $u;
            }
            //print_r($user_meta);
            update_option('sugarpress_users',json_encode($user_meta));
        }
    }
    
    if(!function_exists('user_dropdown')) {
        function user_dropdown($name, $selected = null) {
            if(get_option('sugarpress_users') == null) {
                sugarpress_refresh_users();
            }
            $users = json_decode(get_option('sugarpress_users'));
            //$users = get_option('sugarpress_users');
            //return $users;
            $html = "<select name='$name'>";
            foreach($users as $user) {
                $html .= "<option value='$user->id' ".($selected == $user->id ? "selected": "").">".$user->full_name."</option>";
            }
            $html .= "</select>";
            return $html;
        }
    }

    if(!function_exists('get_sugar_user_full_name')) {
        function get_sugar_user_full_name($id) {
            $users = json_decode(get_option('sugarpress_users'));
            foreach($users as $user) {
                if($id == $user->id) return $user->full_name;
            }
            return null;
        }
    }

    if(!function_exists('wp_multiselect_pages')) {
        function wp_multiselect_pages($args = array()) {
            $pages = get_pages();
            $html = "<select ".(isset($args['name']) ? "name='".$args['name']."[]'" : "")." multiple>";
            foreach($pages as $page){
                //print_r($page);
                $selected = (isset($args['selected']) ? (in_array($page->ID,$args['selected']) ? "selected":""):"");
                $html .= "<option value='".$page->ID."' $selected>".$page->post_title."</option>";
            }
            $html .= "</select>";
            echo $html;
        }
    }
    
    if(!function_exists('sugarform_post')) {
        function sugarform_post($module, $fields, $post_data) {
            //	construct our data object
            $data = array();
            $add_attachments = false;
            $files = array();
            foreach($fields as $field) {
                $f = $field->name;
				if($field->type == 'heading') { continue; }
				if($field->type == 'file') {
					//	get temp file location
					$add_attachments = true;
					if ($_FILES[$f]["error"] > 0) {
					  echo "Error: " . $_FILES[$f]["error"] . "<br />";
					}
					else {
						$allowedExts = array("doc","docx","xls","xlsx","txt","pdf","jpg","jpeg","gif","png");
						$extension = end(explode(".", $_FILES[$f]["name"]));
						if (in_array($extension, $allowedExts)) {
							$files[] = $_FILES[$f];
						}
						//print_r($_FILES[$f]);
					}
					continue;
				}
                if(isset($_POST[$f])) {
                    $val = $_POST[$f];
                    if($field->type == 'checkbox') {
                        $val = ($_POST[$f] == "1" ? 1 : 0);
                    }
                    $data[$field->target_field]=$val;
                }
			}
            
            //  call the api & post data
            $sugar_url = get_option('sugar_url');
            $sugar_user = get_option('sugar_user');
            $sugar_pwd = get_option('sugar_pwd');
            $api = new SugarREST($sugar_url,$sugar_user,$sugar_pwd,false);
            $api->create_record($module,$data);

        }
    }

?>
