<?php

class Thingiverse {
   
  const BASE_URL          = "https://www.thingiverse.com";
  const BASE_API_URL      = "https://api.thingiverse.com";
  const TOKEN_URL         = "https://www.thingiverse.com/api/v2/auth/view";
  const TOKEN_URL_MD5     = "https://cdn.thingiverse.com/site/js/app.bundle.js";
  const CACHE_TTL_TOKEN   = 86400;    //24 h
  const CACHE_TTL_TOKEN_MD5   = 86400;    //24 h
  const CACHE_TTL_THING   = 3600;     //1 h
  const CACHE_TTL_WIDGET  = 3600;     //1 h
  const CACHE_TTL_USER_ID = 2592000;  //1 month
  const CACHE_ENABLE      = true; // for disable all cache for debug only
  const DEBUG             = false;  // for debug

  public static function user_id_from_name( $user ) {

    $user_key = 'thingiverse-user-id-from-name-'.$user;

    $cached_user_id_from_name = Thingiverse::get_object_from_cache($user_key);

  	if(false === $cached_user_id_from_name or $cached_user_id_from_name === ""){

      $obj = Thingiverse::get_authorized_url_json('https://api.thingiverse.com/users/'.$user);
      $id_from_name = $obj->id;
      Thingiverse::log_message("Renewing KEY: ".$user_key."=".$id_from_name);
      set_transient($user_key, $id_from_name, Thingiverse::CACHE_TTL_USER_ID);
      
      return $id_from_name;
  	}
  	else{
  		return $cached_user_id_from_name;
  	}
  }

  public static function get_authorization_token(){
    
    $cached_token_key = 'thingiverse-authorization-token';
  	$cached_token = Thingiverse::get_object_from_cache($cached_token_key);
  	if(false === $cached_token){
        $args = [
            'headers' => [
                'Referer' => Thingiverse::BASE_URL,
                'Accept'  => 'application/json',
            ],
            'timeout' => 15,
        ];

        $response = wp_remote_get(Thingiverse::TOKEN_URL, $args);

        if (is_wp_error($response)) {
            Thingiverse::log_message('Error getting Thingiverse token: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        if (!isset($json['access'])) {
            Thingiverse::log_message('Invalid token response from Thingiverse: ' . $body);
            return false;
        }

        $token = $json['access'];
        Thingiverse::log_message("Renewing KEY: {$cached_token_key}={$token}");
        set_transient($cached_token_key, $token, Thingiverse::CACHE_TTL_TOKEN);
        return $token;
  	}
  	else{
  		return $cached_token;
  	}
  }
  public static function get_authorization_token_md5(){
    
    $cached_token_key = 'thingiverse-authorization-token-md5';
  	$cached_token = Thingiverse::get_object_from_cache($cached_token_key);
  	if(false === $cached_token){
  		$js = file_get_contents(Thingiverse::TOKEN_URL_MD5);
  		//preg_match_all('/="[a-zA-Z0-9]*[a-zA-Z][0-9][a-zA-Z0-9]*+"/', $js, $matches);
      preg_match_all('/\b[a-f0-9]{32}\b/i', $js, $matches);
		  $text = $matches[0];
		  //$token = substr($text[0],strrpos($text[0], '=')+2,-1);
      $token = $text[0];
      Thingiverse::log_message("Renewing KEY: ".$cached_token_key."=".$token);
      set_transient($cached_token_key, $token, Thingiverse::CACHE_TTL_TOKEN_MD5);
      return $token;
  	}
  	else{
  		return $cached_token;
  	}
  }

  //Returns a Json object
  public static function get_authorized_url_json($url, $referer = null, $auth_by_md5 = false){

    if ($referer === null) {
      $referer = Thingiverse::BASE_URL;
    }
    if ($auth_by_md5) {
      $auth_token= Thingiverse::get_authorization_token_md5();
    } else{
      $auth_token=  Thingiverse::get_authorization_token();
    }
    

    $authorization_header = array(
                          'Authorization: Bearer '.$auth_token,
                          'Referer: '.$referer,
                          'Accept: application/json'
                          );
    $options  = ['http' => ['header' => $authorization_header]];
    $context  = stream_context_create($options);
    $json = file_get_contents($url, false, $context);
    $obj = json_decode($json);

    return $obj;
  }

  public static function get_object_from_cache($key){
    
    return Thingiverse::CACHE_ENABLE? get_transient($key):false;

  }

  public static function log_message($message){
    if (Thingiverse::DEBUG){
      error_log(print_r("[Thingiverse-press] ".$message, TRUE));
    }
  }

}
?>
