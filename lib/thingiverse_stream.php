<?php

require_once("thingiverse.php");
require_once("thingiverse_thing.php");

class ThingiverseStream {
  /* Working Stream Types
   * Site-wide:
   *  * featured (/featured, /rss/featured)
   *  * newest (/newest, /rss/newest)
   *  * popular (/popular, /rss/popular)
   *  * derivatives (/derivatives, /rss/derivatives)
   *  * instances (/made-things, /rss/instances)
   * User-specific:
   *  * designed (/<User>/designs, /rss/user:<id>)
   *  * likes (/<User>/favorites, /rss/user:<id>/likes)
   *  * made (/<User>/makes, /rss/user:<id>/made)
   *  * favorites (/<User>/favorites)
   *  * collections (/<User>/collections)
   */
  public $title; // stream title
  public $url;
  public $user; // if any
  public $user_id;
  public $user_url;
  public $things = array();

  function __construct( $type = "newest", $user = null ) {
    $this->user = $user;
    $this->user_url = (is_null($user) ? null : Thingiverse::BASE_URL . "/$user");
    $method_name = "initialize_stream_$type";
    if(method_exists($this, $method_name)){
      call_user_func( array($this, $method_name) );
    } else {
      throw new Exception("Sorry, '$type' streams are not yet supported.");
    }
  }

  function initialize_stream_newest() {
    $this->url = Thingiverse::BASE_URL . "/rss/newest";
    $this->title = "Newest Things";
    $this->load_stream_from_rss_url();
  }

  function initialize_stream_featured() {
    $this->url = Thingiverse::BASE_URL . "/rss/featured";
    $this->title = "Featured Things";
    $this->load_stream_from_rss_url();
  }

  function initialize_stream_popular() {
    $this->url = Thingiverse::BASE_URL . "/rss/popular";
    $this->title = "Popular Things";
    $this->load_stream_from_rss_url();
  }

  function initialize_stream_derivatives() {
    $this->url = Thingiverse::BASE_URL . "/rss/derivatives";
    $this->title = "Newest Derivatives";
    $this->load_stream_from_rss_url();
  }

  // Newest instances has the thing creator, not the instance creator as author.
  // Fall back to HTML parsing.
  function initialize_stream_instances() {
    $this->url = Thingiverse::BASE_URL . "/made-things";
    $this->title = "Newest Instances";
    $this->load_stream_from_instances_url();
  }

  function initialize_stream_designed() {
    $this->user_id = Thingiverse::user_id_from_name($this->user);
    $this->url = Thingiverse::BASE_URL . "/rss/user:$this->user_id";
    $this->title = "Newest Things";
    $this->load_stream_from_rss_url();
  }

  function initialize_stream_likes() {
    $this->user_id = Thingiverse::user_id_from_name($this->user);
    $this->url = Thingiverse::BASE_URL . "/rss/user:$this->user_id/likes";
    $this->title = "Newest Likes";
    $this->load_stream_from_rss_url();
  }

  function initialize_stream_made() {

    $this->user_url = (is_null($this->user) ? null : Thingiverse::BASE_URL . "/$this->user/makes");
    $this->url = Thingiverse::BASE_API_URL . "/users/$this->user/search/?type=makes&sort=newest";
    $cache_id = "thingiverse-press-stream-makes-$this->user";
    $cached_thing = Thingiverse::get_object_from_cache($cache_id);

    if(false === $cached_thing){
      $obj = Thingiverse::get_authorized_url_json($this->url);

      foreach($obj->hits as $key => $lock)
      {
        $thing = new ThingiverseThing();
        $thing -> initialize_from_json($lock);
        array_push($this->things, $thing);
      }

      Thingiverse::log_message("Renewing KEY: ".$cache_id);
      set_transient($cache_id, $this->things, Thingiverse::CACHE_TTL_WIDGET);
    } else {
      $this -> things = $cached_thing;
    }
  }


  function initialize_stream_collections() {

    $this->user_url = (is_null($this->user) ? null : Thingiverse::BASE_URL . "/$this->user/collections");
    $this->url = Thingiverse::BASE_API_URL . "/users/$this->user/search/?type=collections&sort=newest";
    $cache_id = "thingiverse-press-stream-collections-$this->user";
    $cached_thing = Thingiverse::get_object_from_cache($cache_id);

    if(false === $cached_thing){
      $obj = Thingiverse::get_authorized_url_json($this->url);

      foreach($obj->hits as $key => $lock)
      {
        $thing = new ThingiverseThing();
        $thing -> initialize_from_json($lock);
        $thing -> url = $lock -> absolute_url;
        array_push($this->things, $thing);
      }

      Thingiverse::log_message("Renewing KEY: ".$cache_id);
      set_transient($cache_id, $this->things, Thingiverse::CACHE_TTL_WIDGET);
    } else {
      $this -> things = $cached_thing;
    }
  }


  function initialize_stream_favorites() {
    $this->user_url = (is_null($this->user) ? null : Thingiverse::BASE_URL . "/$this->user/favorites");
    $this->url = Thingiverse::BASE_API_URL . "/users/$this->user/favorites";
    $cache_id = "thingiverse-press-stream-favorites-$this->user";
    $cached_thing = Thingiverse::get_object_from_cache($cache_id);

    if(false === $cached_thing){
      $obj = Thingiverse::get_authorized_url_json($this->url);

      foreach($obj as $key => $lock)
      {
        $thing = new ThingiverseThing();
        $thing -> initialize_from_json($lock);
        array_push($this->things, $thing);
      }

      Thingiverse::log_message("Renewing KEY: ".$cache_id);
      set_transient($cache_id, $this->things, Thingiverse::CACHE_TTL_WIDGET);
    } else {
      $this -> things = $cached_thing;
    }
  }

  // Returns a DOM object for the specified URL. Pulls it from the transient
  // cache if it is available, otherwise fetches it.
  function get_dom_for_url($url){
    $dom = new DomDocument("1.0");
    // cache key - chop off "http://www.thingiverse.com" and sluggify
    $t_key = "thingiverse-stream-" . sanitize_title(substr($url,27));
    $dom_str = Thingiverse::get_object_from_cache($t_key);
    if(false === $dom_str){
      @$dom->load($url); // use @ to suppress parser warnings
      $xml_data = $dom->saveXML();
      set_transient($t_key, $xml_data, Thingiverse::CACHE_TTL_WIDGET);
    } else {
      @$dom->loadXML($dom_str); // use @ to suppress parser warnings
    }
    return $dom;
  }

  function load_stream_from_rss_url() {
    $dom = $this->get_dom_for_url($this->url);
    // FIXME: check for parse error. set some kind of thing status!
    $this->parse_things_from_rss_dom($dom);
  }

  function load_stream_from_instances_url() {
    $dom = $this->get_dom_for_url($this->url);
    // FIXME: check for parse error. set some kind of thing status!
    $this->parse_thing_instances_from_html_dom($dom);
  }

  function parse_things_from_rss_dom($dom) {
    $xp = new DomXpath($dom);
    $thing_nodes = $xp->query("//item");
    foreach ($thing_nodes as $thing_node){
      $thing = ThingiverseThing::from_rss_item_dom($thing_node);
      array_push($this->things, $thing);
    }
  }

  function parse_thing_instances_from_html_dom($dom) {
    $xp = new DomXpath($dom);
    $thing_nodes = $xp->query("//div[@class=\"instance_float\"]");
    foreach ( $thing_nodes as $thing_node ) {
      $thing = ThingiverseThing::from_html_instance_dom($thing_node);
      array_push($this->things, $thing);
    }
  }

}
?>