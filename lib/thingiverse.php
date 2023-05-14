<?php

class Thingiverse {
   
  const BASE_URL = "https://www.thingiverse.com";
  const TOKEN_URL = "https://cdn.thingiverse.com/site/js/app.bundle.js";
  const CACHE_TTL_TOKEN   = 86400;    //24 h
  const CACHE_TTL_THING   = 3600;     //1 h
  const CACHE_TTL_WIDGET  = 3600;     //1 h
  const CACHE_TTL_USER_ID = 2592000;  //1 month
  const CACHE_ENABLE      = true; // for disable all cache for debug only
  const DEBUG             = false;  // for debug

  public static function user_id_from_name( $user ) {

    $user_key = 'user_id_from_name_'.$user;

    $cached_user_id_from_name = Thingiverse::get_object_from_cache($user_key);

  	if(false === $cached_user_id_from_name or $cached_user_id_from_name === ""){
      $authorization_header = 'Authorization: Bearer '.Thingiverse::get_authorization_token();
      $options  = ['http' => ['header' => $authorization_header]];
      $context  = stream_context_create($options);
      $json = file_get_contents('https://api.thingiverse.com/users/'.$user, false, $context);
      $obj = json_decode($json);
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
    
    $cached_token_key = 'thingiverse_authorization_token';
  	$cached_token = Thingiverse::get_object_from_cache($cached_token_key);
  	if(false === $cached_token){
  		$js = file_get_contents(Thingiverse::TOKEN_URL);
  		preg_match_all('/,x="\w+/', $js, $matches);
		  $text = $matches[0];
		  $token = substr($text[0],strrpos($text[0], 'x=')+3);
      Thingiverse::log_message("Renewing KEY: ".$cached_token_key."=".$token);
      set_transient($cached_token_key, $token, Thingiverse::CACHE_TTL_TOKEN);
      return $token;
  	}
  	else{
  		return $cached_token;
  	}
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
