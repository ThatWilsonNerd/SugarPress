<?php

    //  test connection
    if(!function_exists('sugarpress_status')) {
        function sugarpress_status() {
            
            //  call the api
            $sugar_url = get_option('sugar_url');
            $sugar_user = get_option('sugar_user');
            $sugar_pwd = get_option('sugar_pwd');
            $api = new SugarREST($sugar_url,$sugar_user,$sugar_pwd,true);
            return $api->loggedIn();
        }
    }

    //  override the wordpress authentication function
    if(get_option('sp_enable_login') == 1) {
        add_filter('authenticate','sugarportal_authenticate',10,3);
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
