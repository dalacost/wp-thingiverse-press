<?php

require_once("thingiverse.php");

class ThingiverseThing {
  public $url;
  public $created_at;
  public $title = "Untitled";
  public $creator = "Unknown";
  public $creator_url = "https://www.thingiverse.com/";
  public $creator_img;
  public $images = array();
  public $main_image;
  public $description;
  public $instructions;
  public $downloads = array();
  public $like_count;
  public $create_date;

  function __construct( $thing_url = "" ) {
    if( $thing_url != null ) {
      // copy from the cache if it exists
      $thing_id = substr($thing_url, strrpos($thing_url, ':') + 1);
      $thing_cache_id = "thingiverse-press-thing-$thing_id";
      $cached_thing = Thingiverse::get_object_from_cache($thing_cache_id);
      if(false === $cached_thing){
        $this->url = $thing_url;
        $obj = Thingiverse::get_authorized_url_json('https://api.thingiverse.com/things/'.$thing_id);
        $this->initialize_from_json($obj);
        //cache
        Thingiverse::log_message("Renewing KEY: ".$thing_cache_id);
        set_transient($thing_cache_id, $this, Thingiverse::CACHE_TTL_THING);
      } else {
        foreach(get_object_vars($cached_thing) as $prop => $value){
          $this->$prop = $value;
        }
      }
    }
  }
  
  function initialize_from_json($obj) {

  	@$this->title 		    = $obj-> name;
  	@$this->creator_url 	= $obj-> creator -> public_url;
  	@$this->creator		    = $obj-> creator -> name;
  	@$this->creator_img	  = $obj-> creator -> thumbnail;
  	@$this->main_image 	  = $obj-> thumbnail;
  	@$this->description	  = $obj-> description;
  	@$this->instructions	= $obj-> instructions;
  	@$this->like_count	  = $obj-> like_count;
  	@$this->create_date	  = $obj-> added;
  	@$this->url	          = $obj-> public_url;
  }

  // From http://php.net/manual/en/function.time.php
  private static function _ago($tm,$rcs = 0) {
    $cur_tm = time(); $dif = $cur_tm-$tm;
    $pds = array('second','minute','hour','day','week','month','year','decade');
    $lngh = array(1,60,3600,86400,604800,2630880,31570560,315705600);
    for($v = sizeof($lngh)-1; ($v >= 0)&&(($no = $dif/$lngh[$v])<=1); $v--); if($v < 0) $v = 0; $_tm = $cur_tm-($dif%$lngh[$v]);
   
    $no = floor($no); if($no <> 1) $pds[$v] .='s'; $x=sprintf("%d %s ",$no,$pds[$v]);
    if(($rcs == 1)&&($v >= 1)&&(($cur_tm-$_tm) > 0)) $x .= time_ago($_tm);
    return $x . "ago";
  }

}
?>
