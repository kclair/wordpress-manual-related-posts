<a href="#" onclick="addNewPost(); return false;">Add new</a>
<script type="text/javascript">
  function addNewPost() {
    // find the select list with the highest identifier
    var posts = '<?php echo($all_posts_string); ?>';
    var size = jQuery('select[name^="manual_related_posts"]').size();
    var newselect = jQuery('<select name="manual_related_posts_'+(size+1)+'"></select>');
    newselect.append('<option value="0">Not Used</option>');
    var this_post;
    var postsArray = posts.split("|");
    var p;
    for (p in postsArray) {
      this_post = postsArray[p].split(":");
      newselect.append('<option value="'+this_post[0]+'">'+this_post[1]+'</option>');
    }
    var newp = jQuery('<p id="mrp_select_list_'+(size+1)+'">Related Post '+(size+1)+': </p>');
    newp.append(newselect)
    var lastname = 'mrp_select_list_'+size;
    newp.insertAfter('p[id="'+lastname+'"]');
  }
</script>
<?php
?>
