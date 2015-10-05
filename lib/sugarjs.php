<?php
    
    //  require our important files
    //  wp stuff?
    require_once('sugarapi-2.0.0.php');
    
    function getApi() {
    
        $sugar_url = 'https://colorfarmrx.sugarondemand.com/rest/v10';
        $sugar_user = 'cynergy2';
        $sugar_pwd = 'Password1';
        
        $api = new SugarREST($sugar_url,$sugar_user,$sugar_pwd,false);
        return $api;
    }
    
    //  process request
    if(isset($_POST['action'])) {
        $path = (isset($_POST['path']) ? $_POST['path'] : '');
        $type = (isset($_POST['type']) ? $_POST['type'] : 'GET');
        $args = array();
        if(isset($_POST['args'])) {
            $args = $_POST['args'];
        }
        //  get api
        $sugar = getApi();
        $url = $sugar->baseUrl().$path;
        $response = $sugar->call($url,$type,$args);
        //print_r($response);
        echo json_encode($response);
    }
?>