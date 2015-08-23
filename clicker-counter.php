<?php
/*
Plugin Name: Clicker Counter
Plugin URI: http://getbutterfly.com/wordpress-plugins-free/
Description: Clicker Counter allows you to track clicks on file extensions or classes. All clicks are logged and ordered by amount.
Version: 2.2.4
Author: Ciprian Popescu
Author URI: http://getbutterfly.com/
License: GPLv2
*/

define('CC_PLUGIN_URL', WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)));
define('CC_PLUGIN_PATH', WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)));
define('CC_PLUGIN_VERSION', '2.2.4');

function ClickerCounter_init() {
	add_option('cc_filetype', '');
	add_option('cc_class', '');
	add_option('cc_relationship', '');
	add_option('cc_custom', '');
}

add_action('init', 'ClickerCounter_init');
add_action('wp_head', 'ClickerCounter_ajaxurl');

function ClickerCounter_ajaxurl() {
    ?>
    <script>var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';</script>
    <?php
}

function my_reverse_array($a, $b) { if($a['count'] == $b['count']) { return 0; } return ($a['count'] > $b['count']) ? -1 : 1; }

function ClickerCounter_get_attachment_id_by_url($url) {
    $parse_url  = explode( parse_url( WP_CONTENT_URL, PHP_URL_PATH), $url);
    $this_host = str_ireplace('www.', '', parse_url(home_url(), PHP_URL_HOST));
    $file_host = str_ireplace('www.', '', parse_url($url, PHP_URL_HOST));

    if(!isset($parse_url[1]) || empty($parse_url[1]) || ($this_host != $file_host))
        return;

    global $wpdb;
    $prefix = $wpdb->prefix;
    $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM " . $prefix . "posts WHERE guid RLIKE %s;", $parse_url[1]));
    return $attachment[0];
}

// add inline jQuery to head
function ClickerCounter_head() {
    $cc_filetype = get_option('cc_filetype');
    $cc_class = get_option('cc_class');
    $cc_relationship = get_option('cc_relationship');
    $cc_custom = get_option('cc_custom');

    // check file type
    if(!empty($cc_filetype)) {
        ?>
        <script>jQuery(document).ready(function(){jQuery('a[href$=".<?php echo $cc_filetype; ?>"]').addClass("outgoing"),jQuery('a[href$=".<?php echo $cc_filetype; ?>"]').click(function(){var e={action:"outgoing_count",link:this.href,page:window.location.pathname};jQuery.post(ajaxurl,e)})});</script>
        <?php
    }

    // check file class
    if(!empty($cc_class)) {
        ?>
        <script>jQuery(document).ready(function(){jQuery(".<?php echo $cc_class; ?>").addClass("outgoing"),jQuery(".<?php echo $cc_class; ?>").click(function(){var c={action:"outgoing_count",link:this.href,page:window.location.pathname};jQuery.post(ajaxurl,c)})});</script>
        <?php
    }

    // check file relationship
    if(!empty($cc_relationship)) {
        ?>
        <script>jQuery(document).ready(function(){jQuery('a[rel="<?php echo $cc_relationship; ?>"]').addClass("outgoing"),jQuery('a[rel="<?php echo $cc_relationship; ?>"]').click(function(){var o={action:"outgoing_count",link:this.href,page:window.location.pathname};jQuery.post(ajaxurl,o)})});</script>
        <?php
    }

    // check custom selector
    if(!empty($cc_custom)) {
        ?>
        <script>jQuery(document).ready(function(){jQuery("<?php echo $cc_custom; ?>").addClass("outgoing"),jQuery("<?php echo $cc_custom; ?>").click(function(){var o={action:"outgoing_count",link:this.href,page:window.location.pathname};jQuery.post(ajaxurl,o)})});</script>
        <?php
    }
}
add_action('wp_head', 'ClickerCounter_head');

add_action('admin_menu', 'ClickerCounter_plugin_menu');
add_action('admin_menu', 'CLickerCounter_admin_menu');

function ClickerCounter_admin_menu() {
    if(isset($_GET['page']) && $_GET['page'] == 'cppie') {
        wp_register_style('admin-style', plugins_url('/css/as.min.css', __FILE__));
        wp_enqueue_style('admin-style');
    }
}

function ClickerCounter_plugin_menu() {
    add_options_page('Clicker Counter', 'Clicker Counter', 'manage_options', 'cppie', 'ClickerCounter_plugin_options');
}

function ClickerCounter_plugin_options() {
    $form_actions = '';

    if(isset($_POST['delete-statistics-button'])) {
        ClickerCounter_reset_statistics();
        $form_actions = "Statistics have been wiped clean.";
    }

    $all = array();
    ?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"></div>
		<h2>Clicker Counter Settings</h2>
		<?php
		$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard_tab';
		if(isset($_GET['tab']))
			$active_tab = $_GET['tab'];
		?>
		<h2 class="nav-tab-wrapper">
			<a href="options-general.php?page=cppie&amp;tab=dashboard_tab" class="nav-tab <?php echo $active_tab == 'dashboard_tab' ? 'nav-tab-active' : ''; ?>"><?php _e('Dashboard', 'cppie'); ?></a>
			<a href="options-general.php?page=cppie&amp;tab=click_filtered_tab" class="nav-tab <?php echo $active_tab == 'click_filtered_tab' ? 'nav-tab-active' : ''; ?>"><?php _e('Click Statistics', 'cppie'); ?></a>
			<a href="options-general.php?page=cppie&amp;tab=click_raw_tab" class="nav-tab <?php echo $active_tab == 'click_raw_tab' ? 'nav-tab-active' : ''; ?>"><?php _e('Raw Statistics', 'cppie'); ?></a>
			<a href="options-general.php?page=cppie&amp;tab=maintenance_tab" class="nav-tab <?php echo $active_tab == 'maintenance_tab' ? 'nav-tab-active' : ''; ?>"><?php _e('Settings and Maintenance', 'cppie'); ?></a>
		</h2>

        <?php if($active_tab == 'dashboard_tab') { ?>
			<div id="poststuff" class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h3><?php _e('Dashboard', 'cppie'); ?></h3>
					<div class="inside">
				        <p>Thank you for using <b>Clicker Counter</b>, a plugin which allows you to track clicks on file extensions or classes. All clicks are logged and ordered by amount.</p>
                        <p><small>You are using <b>Clicker Counter</b> plugin version <strong><?php echo CC_PLUGIN_VERSION; ?></strong>.</small></p>

                        <h4>Help and support</h4>
                        <p>Visit the <b>Settings and Maintenance</b> tab and fill in one or both text fields. For example, if you want to track the global downloads of PDF files on your site, you need to enter <code>pdf</code> in the file extension field. If you want to manually add a class for your tracked assets, enter the class name in file class field, such as <code>myclass</code>. The plugin also allows for relationship attributes, such as <code>rel="lightbox"</code> or <code>rel="external"</code>. For best results, only use one of the available options.</p>

                        <h4>Related Documentation Links</h4>
                        <p>
                            <a href="http://api.jquery.com/category/selectors/attribute-selectors/">jQuery attribute selectors [1]</a><br>
                            <a href="http://codylindley.com/jqueryselectors/">jQuery attribute selectors [2]</a>
                        </p>
                    </div>
                </div>
                <div class="postbox">
                    <div class="inside">
                        <p>For support, feature requests and bug reporting, please visit the <a href="//getbutterfly.com/" rel="external">official website</a>.</p>
                        <p>&copy;<?php echo date('Y'); ?> <a href="//getbutterfly.com/" rel="external"><strong>getButterfly</strong>.com</a> &middot; <a href="//getbutterfly.com/forums/" rel="external">Support forums</a> &middot; <a href="//getbutterfly.com/trac/" rel="external">Trac</a> &middot; <small>Code wrangling since 2005</small></p>
                    </div>
                </div>
            </div>
        <?php } ?>
		<?php if($active_tab == 'click_filtered_tab') { ?>
			<div id="poststuff" class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h3><?php _e('Click Statistics', 'cppie'); ?></h3>
					<div class="inside">
                        <?php
                        $meta_values = maybe_unserialize(get_post_meta(1, 'link', true));

                        if(is_array($meta_values) && count($meta_values) > 0) {
                            echo '<h2>File Clicks</h2>';
                            $it = 0;

                            // compare array and order by count
                            @usort($meta_values, "my_reverse_array");
                            foreach($meta_values as $meta_value) {
                                $it++;
                                ?>
                                <div class="link-item<?php echo (($it % 2 == 0) ? ' even' : ''); ?>">
                                    <div class="link-url">
                                        <?php $pid = ClickerCounter_get_attachment_id_by_url($meta_value['link']); ?> <?php echo $meta_value['link']; ?>
                                        <?php
                                        $title = get_the_title($pid);
                                        if(!empty($title)) {
                                            ?>
                                            <br>
                                            Attached as <b><?php echo $title; ?></b> to 
                                            <?php
                                            $parent = get_post_field('post_parent', $pid);
                                            $link = get_permalink($parent);

                                            echo '<a href="' . $link . '" target="_blank">' . get_the_title($parent) . '</a>';
                                            echo ' in <b>';
                                            $postid = url_to_postid($link);

                                            echo get_the_category_list(', ', '', $postid);
                                            echo '</b>';
                                        }
                                        ?>
                                    </div>
                                    <div class="link-count"><?php echo $meta_value['count']; ?></div>
                                    <div class="link-object-actions"></div>
                                </div>
                                <?php
                                $all[$meta_value['link']] = $meta_value['count'];
                            }
                        }

                        $args = array(
                            'numberposts' => 1000,
                            'offset' => 0,
                            'orderby' => 'post_date',
                            'order' => 'DESC',
                            'post_type' => 'post',
                            'post_status' => 'publish'
                        );

                        $posts_array = get_posts($args);
                        foreach($posts_array as $post) {
                            $meta_values = maybe_unserialize(get_post_meta($post->ID, 'link', true));
                            if(is_array($meta_values) && count($meta_values) > 0) {
                                ?>
                                <p><b><?php echo $post->post_title; ?></b> (<a href="<?php echo $post->guid; ?>" target="_blank">Link</a>)</p>
                                <?php
                                $it = 0;
                                foreach($meta_values as $meta_value) {
                                    $it++;
                                    ?>
                                    <div class="link-item<?php echo (($it % 2 == 0) ? ' even' : ''); ?>">
                                        <div class="link-url"><?php echo $meta_value['link']; ?></div>
                                        <div class="link-count"><?php echo $meta_value['count']; ?></div>
                                        <div class="link-object-actions"></div>
                                    </div>
                                    <?php
                                    if($all[$meta_value['link']]) {
                                        $count = $all[$meta_value['link']] + $meta_value['count'];
                                    } else {
                                        $count = $meta_value['count'];
                                    }
                                    $all[$meta_value['link']] = $count;
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
		<?php } ?>
		<?php if($active_tab == 'click_raw_tab') { ?>
			<div id="poststuff" class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h3><?php _e('Raw Statistics', 'cppie'); ?></h3>
					<div class="inside">
                        <?php
                        $meta_values = maybe_unserialize(get_post_meta(1, 'link', true));

                        $it = 0;

                        // compare array and order by count
                        @usort($meta_values, "my_reverse_array");
                        foreach($meta_values as $meta_value) {
                            $it++;
                            $all[$meta_value['link']] = $meta_value['count'];
                        }

                        $it = 0;
                        foreach($all as $link => $count) {
                            $it++;
                            ?>
                            <div class="link-item<?php echo (($it % 2 == 0) ? ' even' : ''); ?>">
                                <div class="link-url"><?php echo $link ?></div>
                                <div class="link-count"><?php echo $count; ?></div>
                                <div class="link-object-actions"></div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
		<?php } ?>
		<?php if($active_tab == 'maintenance_tab') { ?>
			<?php
			if(isset($_POST['isCCSubmit'])) {
				update_option('cc_filetype', sanitize_text_field($_POST['cc_filetype']));
				update_option('cc_class', sanitize_text_field($_POST['cc_class']));
				update_option('cc_relationship', sanitize_text_field($_POST['cc_relationship']));
				update_option('cc_custom', sanitize_text_field($_POST['cc_custom']));

				echo '<div class="updated"><p>Settings updated successfully!</p></div>';
			}
			?>
			<div id="poststuff" class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h3><?php _e('Settings and Maintenance', 'cppie'); ?></h3>
					<div class="inside">
                        <?php if(!empty($form_actions)) : ?>
                            <div class="bc-form-action-performed"><?php echo $form_actions; ?></div>         
                        <?php endif; ?>

						<form method="post" action="">
							<p>
								<input type="text" class="regular-text" name="cc_filetype" id="cc_filetype" value="<?php echo get_option('cc_filetype'); ?>">
								<label for="cc_filetype">File extension</label>
								<br><small>What file extension to track (pdf, zip, doc, xls, and so on). Only add one extension.</small>
							</p>
							<p>
								<input type="text" class="regular-text" name="cc_class" id="cc_class" value="<?php echo get_option('cc_class'); ?>">
								<label for="cc_class">File class</label>
								<br><small>What file class to track. Only add one class.</small>
                                <br><small>Example: <code>&lt;a href="file.zip" class="<b>myclass</b>"&gt;Download&lt;/a&gt;</code> Add <code><b>myclass</b></code> in the box above.</small>
							</p>
							<p>
								<input type="text" class="regular-text" name="cc_relationship" id="cc_relationship" value="<?php echo get_option('cc_relationship'); ?>">
								<label for="cc_relationship">File relationship</label>
								<br><small>What file relationship to track. Only add one relationship.</small>
                                <br><small>Example: <code>&lt;a href="file.zip" rel="<b>external</b>"&gt;Download&lt;/a&gt;</code> Add <code><b>external</b></code> in the box above.</small>
							</p>

                            <hr>
                            <h2>Create your own custom selector</h2>
                            <small>
                                Create your own jQuery selector. Example: <code>$('<b>a[rel!="nofollow"]</b>')</code> or <code>$('<b>a</b>')</code>.
                                <br>For advanced users only. Check the plugin's dashboard for relevant links.
                            </small>
							<p>
								<h2>$('<input type="text" class="regular-text" name="cc_custom" id="cc_custom" value="<?php echo get_option('cc_custom'); ?>">')</h2>
							</p>

                            <p>
								<input type="submit" name="isCCSubmit" value="Save Changes" class="button-primary">
							</p>
						</form>

                        <hr>

                        <form method="post" action="<?php echo get_admin_url() . 'options-general.php?page=cppie'; ?>" name="blankcounter_settings-delete-statistics">
                            <p><input type="submit" value="Delete all statistics" name="delete-statistics-button" class="button button-secondary"></p>
                        </form>
                    </div>
                </div>
            </div>
		<?php } ?>
        </div>
    <?php
    }

add_action('wp_ajax_outgoing_count', 'ClickerCounter_outgoing_callback');
add_action('wp_ajax_nopriv_outgoing_count', 'ClickerCounter_outgoing_callback');

function ClickerCounter_outgoing_callback() {
    if(is_not_post_nor_page(intval($_POST['page']))) {
        $page = 1;
    }
    else {
        $page = url_to_postid(intval($_POST['page']));
    }

    $esc_link = esc_url($_POST['link']);
    $links = maybe_unserialize(get_post_meta($page, 'link', true));

    print_r($links);

    $count = 0;
    $i = 0;

    foreach($links as $link) {
        if($link['link'] == $esc_link) {
            $count = $link['count'];
            break;
        }
        $i++;
    }

    if($count == 0) {
        $links[] = array('link' => $esc_link, 'count' => 1);
    }
    else {
        $count = $count + 1;
        $links[$i] = array('link' => $esc_link, 'count' => $count);
    }

    update_post_meta($page, 'link', serialize($links));
}

function ClickerCounter_reset_statistics() {
    $args = array(
        'numberposts' => 1000,
        'offset' => 0,
        'orderby' => 'post_date',
        'order' => 'DESC',
        'post_type' => 'post',
        'post_status' => 'publish');

    $posts_array = get_posts($args);
    foreach($posts_array as $post) {
        delete_post_meta($post->ID, 'link');
    }
    delete_post_meta(1, 'link');
}

function is_not_post_nor_page($url) {
    $post_page_id = url_to_postid($url);
    if(is_numeric($post_page_id) && $post_page_id > 0)
        return false;
    return true;
}
?>
