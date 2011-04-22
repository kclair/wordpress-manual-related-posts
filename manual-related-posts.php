<?php
/*
Plugin Name: Manual Related Posts 
Plugin URI: 
Description: Define related posts, rather than have wordpress guess them.
Author: Kristina Clair
*/

$rp = new RelatedPosts;

function get_manual_related_posts() {
  $rp = new RelatedPosts;
  return $rp->get_related_posts();
}

class RelatedPosts {
  function __construct() {
    add_action('add_meta_boxes', array(&$this, 'meta_boxes'));
    add_action('save_post', array(&$this, 'save_post'));
  }

  // internal functions
  function id() {
    if (!isset($this->id)) {
      $this->id = get_the_ID();
    }
    return $this->id;
  }

  function get_all_posts() {
    if (!isset($this->all_posts)) {
      $this->all_posts = get_posts(array('numberposts'=>-1));
    }
    return $this->all_posts;
  }

  function postmeta_key($i=null) {
    $base = 'related_post_';
    return(($i) ? $base.$i : $base);
  }

  //
  // template functions
  //

  function get_related_posts() {
    if (!isset($this->related_posts)) {
      global $wpdb;
      $option_name = $this->postmeta_key();
      $result = $wpdb->get_results("SELECT meta_key, meta_value from $wpdb->postmeta where meta_key LIKE '$option_name%' and post_id=".$this->id()." order by meta_key");
      foreach ($result as $id) {
        if (preg_match('/.*(\d+)$/', $id->meta_key, &$matches)) {
          $p = get_post($id->meta_value);
          // do not include invalid records which include poss that are not currently published
          if ($p && ($p->post_status == 'publish')) {
            $this->related_posts[$matches[1]] = $id->meta_value;
          }
        }
      }
    }
    return $this->related_posts;
  }

  function get_related_post($i) {
    global $wpdb;
    $option_name = $this->postmeta_key($i);
    $result = $wpdb->get_results("SELECT meta_value from $wpdb->postmeta where meta_key LIKE '$option_name' and post_id=".$this->id()." order by meta_key");
    return $result[0]->meta_value;
  }

  // 
  // admin functions
  //
  function meta_boxes() {
    add_meta_box('manual-related-posts', 'Related Posts', array(&$this, 'show_post_meta_box'), 'post', 'normal', 'high'); 
  }

  function save_post($post_id) {
    // this is all authentication stuff
    if ( !wp_verify_nonce( $_POST['manual-related-posts'], plugin_basename(__FILE__) ) )
        return $post_id;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
        return $post_id;
    if ( 'page' == $_POST['post_type'] ) 
    {
      if ( !current_user_can( 'edit_page', $post_id ) )
          return $post_id;
    }
    else
    {
      if ( !current_user_can( 'edit_post', $post_id ) )
          return $post_id;
    }
    // need to collapse the list in case any in the middle were removed
    $update_posts = array();
    $i = 1;
    do{
      $key = $this->postmeta_key($i);
      $val = $_POST['manual_related_posts_'.$i];
      if ($val == 0) {
        delete_post_meta($post_id, $key);
      }else {
        $update_posts[] = $val;
      }
      $i++;
    } while (isset($_POST['manual_related_posts_'.$i]));
    $i = 1;
    foreach ($update_posts as $val) {
      update_post_meta($post_id, $this->postmeta_key($i), $val);
      $i++;
    }
  }

  function show_post_meta_box() {
    wp_nonce_field(plugin_basename(__FILE__), 'manual-related-posts');
    $html = '';
    for ($i = 1; $i <= (count($this->get_related_posts())+1); $i++) {
      $html .= '<p id="mrp_select_list_'.$i.'">';
      $html .= 'Related Post '.$i.': <select name="manual_related_posts_'.$i.'">';
      $html .= '<option value=0>Not Used</option>';
      $html .= $this->select_list($i);
      $html .= '</select>';
      $html .= '</p>';
    }
    $html .= $this->add_post_js();
    echo $html;
  }

  function select_list($i) {
    $related_post = $this->get_related_post($i);
    $html = '';
    foreach ($this->get_all_posts() as $p) {
      $selected = (isset($related_post) && ($related_post == $p->ID)) ? " selected" : "";
      $html .= '<option value="'.$p->ID.'" '.$selected.'>'.$p->post_title.'</option>';
    }
    return $html;
  }

  function add_post_js() {
    $mydir = ABSPATH . '/wp-content/plugins/manual-related-posts/';
    $all_posts = array();
    $all_posts_string = '';
    foreach ($this->get_all_posts() as $p) {
      $all_posts[] = str_replace("'", "\\'", $p->ID.":".$p->post_title);
    }
    $all_posts_string = implode("|", $all_posts);
    include($mydir.'/add_post_js.php'); 
  }

}

?>
