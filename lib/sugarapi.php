<?php

    /*
    *   NOTE: Debugging can be set at either the class level (use wisely) 
    *       or the function level (preferred - not as verbose)
    *
    *
    */

    class SugarREST {
    
        private $url;
        private $user;
        private $pwd;
        private $debug = false;
        private $session_id;


        private $oauthtoken = '';
        private $error = false;
        private $error_msg = '';
        
        //  constructor
        function __construct($url,$user,$pwd,$debug = false) {
            $this->url = $url;
            $this->user = $user;
            $this->pwd = $pwd;
            $this->debug = $debug;
            $this->login();
        }
    
        function baseUrl() { return $this->url; }
    
        function login($debug = false) {
            $login_parameters = array(
                "user_auth" => array(
                    "user_name" => $this->user,
                    "password" => md5($this->pwd),
                    "version" => "1"
                ),
                "application" => "SugarForms",
                "name_value_list" => array(),
            );

            $login_result = $this->rest("login", $login_parameters, $this->url);

            if($debug || $this->debug) {
                echo "<pre>";
                print_r($login_result);
                echo "</pre>";
            }
            //get session id
            $this->session_id = $login_result->id;
            return $this->session_id;
        }
        
        function loggedIn() {
            return ($this->session_id != '');
        }
        
        function logout($debug = false) {
            $logout_parameters = array(
                "session" => $this->session_id
            );
            $response = $this->rest("logout",$logout_parameters, $this->url);
            if($debug || $this->debug) {
                echo "<pre>";
                print_r($response);
                echo "</pre>";
            }
        }
        
        function filter($module, $request, $debug = false) {
            $url = $this->url . "/$module/filter";
            
            $response = $this->call($url,'GET',$request);
        
            if($debug || $this->debug) {
                echo "<pre>";
                print_r($response);
                echo "</pre>";
            }
            return $response;
        }
        
        function moduleIgnoreList() {
            return array("ACLRoles","Audit","Currencies","EmailTemplates","Feeds","Filters","Home","iFrames","InboundEmail",
            "MergeRecords","Notifications","OAuthKeys","OAuthTokens","Subscriptions","SugarFavorites","Sync","Teams","UpgradeWizard",
            "UserSignatures","WebLogicHooks","Workflow");
        }
        
        function getModules($debug = false) {
            $request = array(
		"session" => $this->session_id,
		"filter" => "default"
);
            $response = $this->rest("get_available_modules",$request,$this->url);
	//print_r($response);
            $modules = new stdClass();
            foreach($response->modules as $module) {
        	//print_r($module);  
	      //  skip certain modules
		$key = $module->module_key;
                if(!in_array($key,$this->moduleIgnoreList())) {
                    $modules->$key = new stdClass();
		    $modules->$key->label = $module->module_label;
 		    //	get module fields
		    $fields = $this->rest("get_module_fields",array("session"=>$this->session_id, "module"=>$key),$this->url);
			//print_r($fields);
		$modules->$key->fields = $fields;
                }
            }
            if($debug || $this->debug) {
                echo "<pre>";
                print_r($modules);
                echo "</pre>";
            }
            return $modules;
        }
        
        function getUsers($debug = false) {
            $url = $this->url ."/Users";
            $request = array();
            $response = $this->call($url,'GET',$request);
            if($debug || $this->debug) {
                echo "<pre>";
                print_r($response->records);
                echo "</pre>";
            }
            return $response->records;
        }
    
        //function to make cURL request
        function rest($method, $parameters, $url) {
            ob_start();
            $curl_request = curl_init();

            curl_setopt($curl_request, CURLOPT_URL, $url);
            curl_setopt($curl_request, CURLOPT_POST, 1);
            curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
            curl_setopt($curl_request, CURLOPT_HEADER, 1);
            curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);

            $jsonEncodedData = json_encode($parameters);

            $post = array(
                "method" => $method,
                "input_type" => "JSON",
                "response_type" => "JSON",
                "rest_data" => $jsonEncodedData
            );

            curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post);
            $result = curl_exec($curl_request);
            curl_close($curl_request);
            $result = explode("\r\n\r\n", $result, 2);
            $response = json_decode($result[1]);
            ob_end_flush();

            return $response;
        }
        /*
        function call($url, $type='GET', $arguments=array(), $encodeData=true, $returnHeaders=false) {
            $type = strtoupper($type);

            if ($type == 'GET'){
                $url .= "?" . http_build_query($arguments);
            }

            $curl_request = curl_init($url);

            if ($type == 'POST'){
                curl_setopt($curl_request, CURLOPT_POST, 1);
            }
            elseif ($type == 'PUT'){
                curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, "PUT");
            }
            elseif ($type == 'DELETE'){
                curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, "DELETE");
            }

            curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
            curl_setopt($curl_request, CURLOPT_HEADER, $returnHeaders);
            curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);

            if (!empty($this->oauthtoken)){
                $token = array("oauth-token: {$this->oauthtoken}");
                curl_setopt($curl_request, CURLOPT_HTTPHEADER, $token);
            }
            else {
                //   not logged in
                
            }

            if (!empty($arguments) && $type !== 'GET'){
                if ($encodeData){
                    //encode the arguments as JSON
                    $arguments = json_encode($arguments);
                }
                curl_setopt($curl_request, CURLOPT_POSTFIELDS, $arguments);
            }
            $result = curl_exec($curl_request);
            curl_close($curl_request);

            if($this->debug) {
                print_r($result);
            }

            if ($returnHeaders){
                //set headers from response
                list($headers, $content) = explode("\r\n\r\n", $result ,2);
                foreach (explode("\r\n",$headers) as $header){
                    header($header);
                }
                //return the nonheader data
                return trim($content);
            }

            //decode the response from JSON
            $response = json_decode($result);
            return $response;
        }
        */

        function create_record($module, $data, $debug=false) {
            $url = $this->url ."/$module";
            $response = $this->call($url,'POST',$data);
            if($debug || $this->debug) {
                echo "<pre>";
                print_r($response->id);
                echo "</pre>";
            }
            return $response->id;
        }

    }

?>
