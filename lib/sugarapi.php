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
            $url = $this->url . "/oauth2/token";

            $oauth2_token_request = array(
                "grant_type" => "password",
                //client id/secret you created in Admin > OAuth Keys
                "client_id" => "sugar",
                "client_secret" => "",
                "username" => $this->user,
                "password" => $this->pwd,
                "platform" => "sugarpress"
            );

            if($debug || $this->debug) {
                echo "<pre>";
                print_r($oauth2_token_request);
                echo "</pre>";
            }

            $oauth2_token_response = $this->call($url, 'POST', $oauth2_token_request);
            
            if($debug || $this->debug) {
                echo "<pre>";
                print_r($oauth2_token_response);
                echo "</pre>";
            }
            
            //  catch errors
            if(property_exists($oauth2_token_response,'error')) {
                $this->error = true;
                $this->error_msg = 'Login Failed.';//    ?
            }
            else {
                //  save access token
                $this->oauthtoken = $oauth2_token_response->access_token;
            }

        }
        
        function loggedIn() {
            return ($this->oauthtoken != '');
        }
        
        function logout($debug = false) {
            $url = $this->url . "/oauth2/logout";
            
            $response = $this->call($url,'POST');
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
            $url = $this->url ."/metadata";
            $request = array();
            $response = $this->call($url,'GET',$request);
            $modules = new stdClass();
            foreach($response->modules as $key=>$value) {
                //  skip certain modules
                if(!in_array($key,$this->moduleIgnoreList())) {
                    $fields = array();
                    foreach($value as $k=>$v) {
                        if($k=="fields") {
                            foreach($v as $f=>$o) {
                                $fields[] = $f;
                            }
                        }
                    }
                    $modules->$key = new stdClass();
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