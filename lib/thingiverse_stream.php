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

    private function initialize_stream_common($url, $cache_id, $is_hits = true) {

    $cached_thing = Thingiverse::get_object_from_cache($cache_id);

    if (false === $cached_thing) {
      $obj = Thingiverse::get_authorized_url_json($url);
      $items = $is_hits ? $obj->hits : $obj;

      foreach ($items as $key => $lock)
      {
        $thing = new ThingiverseThing();
        $thing->initialize_from_json($lock);
        array_push($this->things, $thing);
      }

      Thingiverse::log_message("Renewing KEY: " . $cache_id);
      set_transient($cache_id, $this->things, Thingiverse::CACHE_TTL_WIDGET);
    } else {
      $this->things = $cached_thing;
    }
  }

  function initialize_stream_newest() {
    $this->title = "Newest Things";
    $this->url = Thingiverse::BASE_URL . "/api/v2/search/things?sort=newest";
    $cache_id = "thingiverse-press-stream-newest-global";
    $this->initialize_stream_common($this->url,$cache_id,true);
  }

  function initialize_stream_featured() {
    $this->title = "Featured Things";
    $this->url = Thingiverse::BASE_URL . "/api/v2/search/things?sort=featured";
    $cache_id = "thingiverse-press-stream-featured-global";
    $this->initialize_stream_common($this->url,$cache_id,true);
  }

  function initialize_stream_popular() {
    $this->title = "Popular Things";
    $this->url = Thingiverse::BASE_URL . "/api/v2/search/things?sort=popular&posted_after=now-30d";
    $cache_id = "thingiverse-press-stream-popular-global";
    $this->initialize_stream_common($this->url,$cache_id,true);
  }

  function initialize_stream_derivatives() {
    $this->title = "Newest Derivatives";
    $this->url = Thingiverse::BASE_URL . "/api/v2/search/things?sort=derivatives";
    $cache_id = "thingiverse-press-stream-derivatives-global";
    $this->initialize_stream_common($this->url,$cache_id,true);
  }

  // Newest instances has the thing creator, not the instance creator as author.
  // Fall back to HTML parsing.
  function initialize_stream_instances() {
    $this->title = "Newest Instances";
    $this->url = Thingiverse::BASE_URL . "/api/v2/search/things?sort=makes";
    $cache_id = "thingiverse-press-stream-instances-global";
    $this->initialize_stream_common($this->url,$cache_id,true);
  }

  function initialize_stream_designed() {
    $this->user_url = (is_null($this->user) ? null : Thingiverse::BASE_URL . "/$this->user/designs");
    $this->url = Thingiverse::BASE_API_URL . "/users/$this->user/search/?type=designs&sort=newest";
    $cache_id = "thingiverse-press-stream-designs-$this->user";
    $this->initialize_stream_common($this->url,$cache_id,true);
  }

  function initialize_stream_likes() {
    $this->user_url = (is_null($this->user) ? null : Thingiverse::BASE_URL . "/$this->user/likes");
    $this->url = Thingiverse::BASE_URL . "/api/users/$this->user/likes";
    $cache_id = "thingiverse-press-stream-likes-$this->user";
    $this->initialize_stream_common($this->url,$cache_id,false);
  }

  function initialize_stream_made() {
    $this->user_url = (is_null($this->user) ? null : Thingiverse::BASE_URL . "/$this->user/makes");
    $this->url = Thingiverse::BASE_API_URL . "/users/$this->user/search/?type=makes&sort=newest";
    $cache_id = "thingiverse-press-stream-makes-$this->user";
    $this->initialize_stream_common($this->url,$cache_id,true);
  }

  function initialize_stream_collections() {
    $this->user_url = (is_null($this->user) ? null : Thingiverse::BASE_URL . "/$this->user/collections");
    $this->url = Thingiverse::BASE_API_URL . "/users/$this->user/search/?type=collections&sort=newest";
    $cache_id = "thingiverse-press-stream-collections-$this->user";
    $this->initialize_stream_common($this->url,$cache_id,true);
  }

  function initialize_stream_favorites() {
    $this->user_url = (is_null($this->user) ? null : Thingiverse::BASE_URL . "/$this->user/favorites");
    $this->url = Thingiverse::BASE_API_URL . "/users/$this->user/favorites";
    $cache_id = "thingiverse-press-stream-favorites-$this->user";
    $this->initialize_stream_common($this->url,$cache_id,false);
  }
}
?>