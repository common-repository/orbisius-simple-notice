<?php
/*
  Plugin Name: Orbisius Simple Notice
  Plugin URI: https://orbisius.com/products/wordpress-plugins/orbisius-simple-notice/
  Description: This plugin allows you to show a simple notice to alert your users about server maintenance, new product launches etc.
  Version: 1.1.2
  Author: Svetoslav Marinov (Slavi)
  Author URI: https://orbisius.com
 */

/*  Copyright 2012-2050 Svetoslav Marinov (Slavi) <slavi@orbisius.com>

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Set up plugin
add_action('init', 'orbisius_simple_notice_init');
add_action('wp_footer', 'orbisius_simple_notice_inject_notice', 50, 0); // be the last in the footer
add_action('admin_init', 'orbisius_simple_notice_admin_init');
add_action('admin_menu', 'orbisius_simple_notice_setup_admin');

/**
 * Adds the action link to settings. That's from Plugins. It is a nice thing.
 * @param type $links
 * @param type $file
 * @return type
 */
function orbisius_simple_notice_add_quick_settings_link($links, $file) {
    if ($file == plugin_basename(__FILE__)) {
        $link = admin_url('options-general.php?page=' . plugin_basename(__FILE__));
        $dashboard_link = "<a href=\"{$link}\">Settings</a>";
        array_unshift($links, $dashboard_link);
    }

    return $links;
}

/**
 * Setups loading of assets (css, js).
 * for live servers we'll use the minified versions e.g. main.min.js otherwise .js or .css (dev)
 * @see https://jscompress.com/ - used for JS compression
 * @see https://refresh-sf.com/yui/ - used for CSS compression
 * @return type
 */
function orbisius_simple_notice_init() {
    $dev = empty($_SERVER['DEV_ENV']) ? 0 : 1;
    $suffix = $dev ? '' : '.min';

    /*wp_register_style('simple_notice', plugins_url("/assets/main{$suffix}.css", __FILE__));
    wp_enqueue_style('simple_notice');*/

    $opts = orbisius_simple_notice_get_options();

    // load cookies only if the user wants to have a close button.
    if (!empty($opts['show_close_button'])) {
        wp_register_script('simple_notice', plugins_url("/assets/jquery.cookie$suffix.js", __FILE__), array('jquery', ), '1.0', true);
        wp_enqueue_script('simple_notice');
    }
}

function orbisius_simple_notice_admin_init() {
    orbisius_simple_notice_register_settings();

    global $wp_version;

    $color_picker = version_compare($wp_version, '3.5') >= 0 ? 'wp-color-picker' // new WP
            : 'farbtastic'; // old WP

    wp_enqueue_style($color_picker);
    wp_enqueue_script($color_picker);

    wp_register_script('simple_notice_admin', plugins_url("/assets/admin_main.js", __FILE__), array('jquery',), '1.0', true);
    wp_enqueue_script('simple_notice_admin');
}

/**
 * Outputs the feedback form + container. if the user is logged in we'll take their email
 * requires: wp_footer
 */
function orbisius_simple_notice_inject_notice($is_site_front_end = 1) {
    $opts = orbisius_simple_notice_get_options();
    $data = orbisius_simple_notice_get_plugin_data();

    // The user doesn't want to show the notice.
    // This applies only for the live site.
    if ( (empty($opts['status']) && !empty($is_site_front_end))
			|| defined( 'DOING_AJAX' ) ) {
		echo "\n<!-- {$data['name']} | {$data['url']} : is disabled or it's an ajax call. Skipping rendering. -->\n";
        return;
    }

    $close_button_line = $js_code = '';
    
    // Enable close dismiss button
    if (!empty($opts['show_close_button'])) {
        $close_button_line =
            "<div class='orbisius_simple_notice_dismiss_container'>"
            . "<a title='Click to close/dismiss message.' "
            . "href='javascript:void(0);' class='dismiss_message'>[X] Close</a>"
            . "</div>\n";

        $js_code .= <<<FORM_EOF
    <script>
    jQuery(document).ready(function ($) {
        var msg_id = jQuery('#orbisius_simple_notice').data('msg_id');

        if (!jQuery('body').hasClass('wp-admin')) { // public area only
            var orb_simp_ntc_dismiss_hash = jQuery.cookie('orb_simp_ntc_dismiss') || '';

            /* if the cookie exists and matches the msg_id that means the user has dismissed the message already. So don't show it for another day. */
            if (orb_simp_ntc_dismiss_hash == msg_id) {
                jQuery('#orbisius_simple_notice_container').hide();
            }
        }

        // The user has clicked on the X sign
        jQuery('#orbisius_simple_notice_container .dismiss_message').on('click', function() {
            jQuery('#orbisius_simple_notice_container').slideUp('slow');

            // for WP admin show the message.
            if (jQuery('body').hasClass('wp-admin')) {
                setTimeout(function () {
                    jQuery('#orbisius_simple_notice_container').slideDown('slow');
                }, 2000);
            } else {
                // this will be empty for new messages or expired cookies
                var orb_simp_ntc_dismiss_hash = jQuery.cookie('orb_simp_ntc_dismiss') || '';

                if (orb_simp_ntc_dismiss_hash == '') {
                    jQuery.cookie('orb_simp_ntc_dismiss', msg_id, { expires: 2 } );
                }
            }
        });
    });
</script>
FORM_EOF;
    }

    $powered_by_line = '';

    if (!empty($opts['show_powered_by'])) {
        $host = $_SERVER['HTTP_HOST'];
        $powered_by_url_trk = $data['url'] . "?utm_source=orbisius-simple-notice&utm_medium=$host&utm_campaign=powered_by";

        $powered_by_line =
            "<span class='orbisius_simple_notice_powered_by_container'><div class='orbisius_simple_notice_powered_by'>"
            . "<a title='Powered by {$data['name']}. Free hellobar WordPress Plugin, hellobar,alert notice,notice bar'"
            . " href='$powered_by_url_trk' class='little_info' target='_blank'>i</a>"
            . "</div></span>\n";

        // in case if somebody wants to get rid if the feedback link
        $powered_by_line = apply_filters('orbisius_simple_notice_filter_powered_by', $powered_by_line);
    }

    $notice = empty($opts['notice']) ? '' : $opts['notice'];
    $text_color = empty($opts['text_color']) ? '#000' : $opts['text_color'];
    $text_bg_color = empty($opts['text_bg_color']) ? '#B4CFD3' : $opts['text_bg_color'];
    $link_color = empty($opts['link_color']) ? '' : $opts['link_color'];

    // if the user is logged in WP admin bar is obstructive the view.
    $top_pos = is_user_logged_in() ? '28px' : 0;
    $left_pos = 0;

    $inline_css_arr = array(
        // configurable
        "color:$text_color",
        "background:none repeat scroll 0 0 $text_bg_color",
        // static
        "margin:0",
        "padding:4px 0",
        "text-align:center",
        "width:100%",
        "z-index:1000",
        "font-family:arial",
    );

    // does the user want a bigger font?
    if (!empty($opts['font_size'])) {
        $inline_css_arr[] = 'font-size:' . $opts['font_size'];
    }

    if (!empty($is_site_front_end)) {
        // show only on home page
        if ($opts['show_notice_criteria'] == 'home_page'
                && ( !is_home() && !is_front_page() ) ) {
            echo "\n<!-- {$data['name']} | {$data['url']} : selected to be rendered on home page only. Skipping rendering. -->\n";
            return '';
        }

        if ($opts['show_notice_method'] == 'on_top') {
            $inline_css_arr[] = "top:$top_pos";
            $inline_css_arr[] = "left:$left_pos";
            $inline_css_arr[] = "position:fixed";
        } else {
            $js_code .= <<<FORM_EOF
<script>
    jQuery(document).ready(function ($) {
        jQuery('#orbisius_simple_notice_container').prependTo('body'); // .css('postion', '')
    });
</script>
FORM_EOF;
        }
    }

    $link_color_css = '';
    $inline_css = join(";\n", $inline_css_arr);

    // do we have a specific color for links?. Yep. Include its CSS then.
    if (!empty($link_color)) {
        $link_color_css .= ".orbisius_simple_notice_container .orbisius_simple_notice a,
                .orbisius_simple_notice_container .orbisius_simple_notice a:visited {
                color: $link_color;
            }\n";
    }

    $msg_id = 'orb_ntc_'. md5($notice);
    $notice = do_shortcode($notice);

    $form_buff = <<<FORM_EOF
<!-- orbisius_simple_notice : {$data['name']} | {$data['url']} -->
<style>
.orbisius_simple_notice_container .orbisius_simple_notice {
    $inline_css
}

.orbisius_simple_notice_container a {
    text-decoration:underline;
}

$link_color_css

.orbisius_simple_notice_powered_by_container {
    float:left;
    text-decoration:none;
}

.orbisius_simple_notice_dismiss_container {
    float:right;
    margin-right:3px;
    padding:0px 2px;
}

.orbisius_simple_notice_dismiss_container a {
    text-decoration:none;
    display:inline-block;
}

.orbisius_simple_notice_powered_by_container .little_info {
    text-decoration:none;
    display:block-inline;
    padding:0px 3px;
}
</style>
$js_code
<div id="orbisius_simple_notice_container" class="orbisius_simple_notice_container">
    <div id="orbisius_simple_notice" class="orbisius_simple_notice" data-msg_id="$msg_id">
        $powered_by_line
        $notice
        $close_button_line
    </div> <!-- /orbisius_simple_notice -->
</div> <!-- /orbisius_simple_notice_container -->
<!-- /orbisius_simple_notice -->
FORM_EOF;

//show_notice_method

    echo $form_buff;
}

/**
 * Set up administration
 *
 * @package Orbisius Simple Notice
 * @since 0.1
 */
function orbisius_simple_notice_setup_admin() {
    add_options_page('Orbisius Simple Notice', 'Orbisius Simple Notice', 'manage_options', __FILE__, 'orbisius_simple_notice_options_page');

    add_filter('plugin_action_links', 'orbisius_simple_notice_add_quick_settings_link', 10, 2);
}

/**
 * Sets the setting variables
 */
function orbisius_simple_notice_register_settings() { // whitelist options
    register_setting('orbisius_simple_notice_settings', 'orbisius_simple_notice_options', 'orbisius_simple_notice_validate_settings');
}

/**
 * This is called by WP after the user hits the submit button.
 * The variables are trimmed first and then passed to the who ever wantsto filter them.
 * @param array the entered data from the settings page.
 * @return array the modified input array
 */
function orbisius_simple_notice_validate_settings($input) { // whitelist options
    $input = array_map('trim', $input);

    // let extensions do their thing
    $input_filtered = apply_filters('orbisius_simple_notice_ext_filter_settings', $input);

    // did the extension break stuff?
    $input = is_array($input_filtered) ? $input_filtered : $input;

    // for font size we want 12px or 14pt
    if (!empty($input['font_size'])) {
        $input['font_size'] = preg_replace('#\s#si', '', $input['font_size']);
    }

    return $input;
}

/**
 * Retrieves the plugin options. It inserts some defaults.
 * The saving is handled by the settings page. Basically, we submit to WP and it takes
 * care of the saving.
 * 
 * @return array
 */
function orbisius_simple_notice_get_options() {
    $defaults = array(
        'status' => 0,
        'show_powered_by' => 1,
        'show_in_admin' => 0,
        'show_notice_method' => 'on_top',
        'show_close_button' => 1,
        'show_notice_criteria' => 'all_pages', // home_page
        'text_color' => '#555',
        'text_bg_color' => '#B4CFD3',
        'link_color' => '',
        'font_size' => '',
        'notice' => 'We have launched a new product ...',
    );

    $opts = get_option('orbisius_simple_notice_options');

    $opts = (array) $opts;
    $opts = array_merge($defaults, $opts);

    return $opts;
}

/**
 * Options page
 *
 * @package Orbisius Simple Notice
 * @since 1.0
 */
function orbisius_simple_notice_options_page() {
    $opts = orbisius_simple_notice_get_options();
    global $wp_version;
    ?>
    <div id="orbisius_simple_notice_admin_wrapper" class="wrap orbisius_simple_notice_admin_wrapper">
        <h2>Orbisius Simple Notice</h2>
        <p>
            This plugin allows you to show a simple notice to alert your users about server maintenance, new product launches etc.
        </p>

        <div id="poststuff">

            <div id="post-body" class="metabox-holder columns-2">

                <!-- main content -->
                <div id="post-body-content">

                    <div class="meta-box-sortables ui-sortable">

                        <div class="postbox">
                            <h3><span>Settings</span></h3>
                            <div class="inside">
                                <form method="post" action="options.php">
                                    <?php settings_fields('orbisius_simple_notice_settings'); ?>
                                    <table class="form-table">
                                        <tr valign="top">
                                            <th scope="row">Show Notice</th>
                                            <td>
                                                <label for="orbisius_simple_notice_options_radio1">
                                                    <input type="radio" id="orbisius_simple_notice_options_radio1" name="orbisius_simple_notice_options[status]"
                                                           value="1" <?php echo empty($opts['status']) ? '' : 'checked="checked"'; ?> /> Enabled
                                                </label>
                                                |
                                                <label for="orbisius_simple_notice_options_radio2">
                                                    <input type="radio" id="orbisius_simple_notice_options_radio2" name="orbisius_simple_notice_options[status]"
                                                           value="0" <?php echo !empty($opts['status']) ? '' : 'checked="checked"'; ?> /> Disabled
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Notice</th>
                                            <td>
                                                <?php if (1) : ?>
                                                    <?php
                                                    wp_editor($opts['notice'], "orbisius_simple_notice_options-notice", array(
                                                        'teeny' => true,
                                                        'media_buttons' => false,
                                                        'textarea_rows' => 2,
                                                        'textarea_name' => 'orbisius_simple_notice_options[notice],'
                                                    ));
                                                    ?>
                                                <?php else : // simple editor ?>
                                                    <input type="text" id="orbisius_simple_notice_options_notice" class="widefat"
                                                           name="orbisius_simple_notice_options[notice]"
                                                           value="<?php echo esc_attr($opts['notice']); ?>" />
                                                       <?php endif; ?>
                                                <p>
                                                    Example: We are going to be doing server maintenance at 9pm today.
                                                    <br/>Example: We have just launched a new product ...
                                                </p>
                                            </td>
                                        </tr>
                                        <tr style="background: #eee;">
                                            <th scope="row">Preview </th>
                                            <td>
                                                <?php echo orbisius_simple_notice_inject_notice(0); ?>
                                                <div>Save changes to see a new preview.
                                                    Only in WP admin area: if you click the close button the message will hide and then show up in 2 seconds.</div>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row">Show the Notice Text On</th>
                                            <td>
                                                <label for="show_notice_criteria_radio1">
                                                    <input type="radio" id="show_notice_criteria_radio1" name="orbisius_simple_notice_options[show_notice_criteria]"
                                                           value='all_pages' <?php checked('all_pages', $opts['show_notice_criteria']); ?> /> All pages/posts
                                                </label>
                                                <br/>
                                                <label for="show_notice_criteria_radio2">
                                                    <input type="radio" id="show_notice_criteria_radio2" name="orbisius_simple_notice_options[show_notice_criteria]"
                                                           value='home_page' <?php checked('home_page', $opts['show_notice_criteria']); ?> /> Home Page only
                                                </label>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row">How to Show the Notice Text</th>
                                            <td>
                                                <label for="show_notice_method_radio1">
                                                    <input type="radio" id="show_notice_method_radio1" name="orbisius_simple_notice_options[show_notice_method]"
                                                           value='on_top' <?php checked('on_top', $opts['show_notice_method']); ?> /> On top of existing content
                                                    <br/><small>It will be added on top of all content (with higher z-index value)</small>
                                                </label>
                                                <br/>
                                                <label for="show_notice_method_radio2">
                                                    <input type="radio" id="show_notice_method_radio2" name="orbisius_simple_notice_options[show_notice_method]"
                                                           value='push_down' <?php checked('push_down', $opts['show_notice_method']); ?> /> Push down existing content
                                                    <br/><small>It will be added as first element in the &lt;body&gt; tag</small>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Text Color</th>
                                            <td>
                                                <label for="orbisius_simple_notice_options_text_color">
                                                    <input type="text" id="orbisius_simple_notice_options_text_color" size="7"
                                                           name="orbisius_simple_notice_options[text_color]"
                                                           value="<?php echo esc_attr($opts['text_color']); ?>" />

                                                    <div id="text_color_picker"></div> <!-- Used for old WP color picker WP < 3.5 -->
                                                </label>
                                                <?php if (version_compare($wp_version, '3.5') >= 0) : ?>
                                                    <p>Once you open the color picker, you will need to click outside of it to close it</p>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Text Background Color</th>
                                            <td>
                                                <label for="orbisius_simple_notice_options_text_bg_color">
                                                    <input type="text" id="orbisius_simple_notice_options_text_bg_color" size="7"
                                                           name="orbisius_simple_notice_options[text_bg_color]"
                                                           value="<?php echo esc_attr($opts['text_bg_color']); ?>" />
                                                    <div id="text_bg_color_picker"></div>
                                                </label>
                                                <?php if (version_compare($wp_version, '3.5') >= 0) : ?>
                                                    <p>Once you open the color picker, you will need to click outside of it to close it</p>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Link Color (optional)</th>
                                            <td>
                                                <label for="orbisius_simple_notice_options_link_color">
                                                    <input type="text" id="orbisius_simple_notice_options_link_color" size="7"
                                                           name="orbisius_simple_notice_options[link_color]"
                                                           value="<?php echo esc_attr($opts['link_color']); ?>" />
                                                    <div id="link_color_picker"></div>
                                                </label>
                                                <p>Use this if links don't look good on a selected background.
                                                    <?php if (version_compare($wp_version, '3.5') >= 0) : ?>
                                                        <br/>Once you open the color picker, you will need to click outside of it to close it
                                                    <?php endif; ?>
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Font Size (optional)</th>
                                            <td>
                                                <label for="orbisius_simple_notice_options_font_size">
                                                    <input type="text" id="orbisius_simple_notice_options_font_size" size="7"
                                                           name="orbisius_simple_notice_options[font_size]"
                                                           value="<?php echo esc_attr($opts['font_size']); ?>" />
                                                </label> Example: To change the font size enter e.g. 14pt, 12px or lower/higher number.
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row">Show Close Button</th>
                                            <td>
                                                <label for="show_close_button_radio1">
                                                    <input type="radio" id="show_close_button_radio1" name="orbisius_simple_notice_options[show_close_button]"
                                                           value='1' <?php checked(1, $opts['show_close_button']); ?> /> Yes
                                                </label>
                                                <br/>
                                                <label for="show_close_button_radio2">
                                                    <input type="radio" id="show_close_button_radio2" name="orbisius_simple_notice_options[show_close_button]"
                                                           value='0' <?php checked(0, $opts['show_close_button']); ?> /> No
                                                </label>
                                                <p>When a notice is closed/dismissed it won't be shown again
                                                    until the message is changed or more than 2 days have passed.</p>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row">Show Little Powered By Icon</th>
                                            <td>
                                                <div>
                                                    This is the small <strong>i</strong> icon displayed in the far left of the message.
                                                    We'd appreciate if you leave it enabled.
                                                </div>
                                                <label for="show_powered_by_radio1">
                                                    <input type="radio" id="show_powered_by_radio1" name="orbisius_simple_notice_options[show_powered_by]"
                                                           value='1' <?php checked(1, $opts['show_powered_by']); ?> /> Enabled
                                                </label>
                                                <br/>
                                                <label for="show_powered_by_radio2">
                                                    <input type="radio" id="show_powered_by_radio2" name="orbisius_simple_notice_options[show_powered_by]"
                                                           value='0' <?php checked(0, $opts['show_powered_by']); ?> /> Disabled
                                                </label>
                                            </td>
                                        </tr>
                                        
                                        <?php if (0) : ?>
                                            <?php if (has_action('orbisius_simple_notice_ext_action_render_settings')) : ?>
                                                <tr valign="top">
                                                    <th scope="row"><strong>Extensions (see list)</strong></th>
                                                    <td colspan="1">
                                                    </td>
                                                </tr>
                                                <?php do_action('orbisius_simple_notice_ext_action_render_settings', $opts, $settings_key); ?>
                                            <?php else : ?>
                                                <tr valign="top">
                                                    <!--<th scope="row">Extension Name</th>-->
                                                    <td colspan="2">
                                                        No extensions found.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </table>

                                    <p class="submit">
                                        <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                                    </p>
                                </form>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->

                    </div> <!-- .meta-box-sortables .ui-sortable -->

                </div> <!-- post-body-content -->

                <!-- sidebar -->
                <div id="postbox-container-1" class="postbox-container">

                    <div class="meta-box-sortables">

                        <!-- Hire Us -->
                        <div class="postbox">
                            <h3><span>Hire Us</span></h3>
                            <div class="inside">
                                Hire us to create a plugin/web/SaaS app
                                <br/><a href="https://orbisius.com/page/free-quote/?utm_source=<?php echo str_replace('.php', '', basename(__FILE__));?>&utm_medium=plugin-settings&utm_campaign=product"
                                   title="If you want a custom web/SaaS app/plugin developed contact us. This opens in a new window/tab"
                                    class="button-primary" target="_blank">Get a Free Quote</a>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->
                        <!-- /Hire Us -->

                        <div class="postbox">
                            <h3><span>Newsletter</span></h3>
                            <div class="inside">
                                <!-- Begin MailChimp Signup Form -->
                                <div id="mc_embed_signup">
                                    <?php
                                        $current_user = wp_get_current_user();
                                        $email = empty($current_user->user_email) ? '' : $current_user->user_email;
                                    ?>

                                    <form action="https://WebWeb.us2.list-manage.com/subscribe/post?u=005070a78d0e52a7b567e96df&amp;id=1b83cd2093" method="post"
                                          id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank">
                                        <input type="hidden" value="settings" name="SRC2" />
                                        <input type="hidden" value="orbisius-child-theme-creator" name="SRC" />

                                        <span>Get notified about cool plugins we release</span>
                                        <!--<div class="indicates-required"><span class="app_asterisk">*</span> indicates required
                                        </div>-->
                                        <div class="mc-field-group">
                                            <label for="mce-EMAIL">Email <span class="app_asterisk">*</span></label>
                                            <input type="email" value="<?php echo esc_attr($email); ?>" name="EMAIL" class="required email" id="mce-EMAIL">
                                        </div>
                                        <div id="mce-responses" class="clear">
                                            <div class="response" id="mce-error-response" style="display:none"></div>
                                            <div class="response" id="mce-success-response" style="display:none"></div>
                                        </div>	<div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button-primary"></div>
                                    </form>
                                </div>
                                <!--End mc_embed_signup-->
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->

                        <?php
                            $plugin_data = get_plugin_data(__FILE__);
                            $product_name = trim($plugin_data['Name']);
                            $product_page = trim($plugin_data['PluginURI']);
                            $product_descr = trim($plugin_data['Description']);
                            $product_descr_short = substr($product_descr, 0, 50) . '...';

                            $base_name_slug = basename(__FILE__);
                            $base_name_slug = str_replace('.php', '', $base_name_slug);
                            $product_page .= (strpos($product_page, '?') === false) ? '?' : '&';
                            $product_page .= "utm_source=$base_name_slug&utm_medium=plugin-settings&utm_campaign=product";

                            $product_page_tweet_link = $product_page;
                            $product_page_tweet_link = str_replace('plugin-settings', 'tweet', $product_page_tweet_link);
                        ?>

                        <div class="postbox">
                            <div class="inside">
                                <!-- Twitter: code -->
                                <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
                                <!-- /Twitter: code -->

                                <!-- Twitter: Orbisius_Follow:js -->
                                    <a href="https://twitter.com/orbisius" class="twitter-follow-button"
                                       data-align="right" data-show-count="false">Follow @orbisius</a>
                                <!-- /Twitter: Orbisius_Follow:js -->

                                <!-- Twitter: Tweet:js -->
                                <a href="https://twitter.com/share" class="twitter-share-button"
                                   data-lang="en" data-text="Checkout <?php echo esc_attr($product_name); ?> #WordPress #plugin."
                                   data-count="none" data-via="orbisius" data-related="orbisius"
                                   data-url="<?php echo $product_page_tweet_link;?>">Tweet</a>
                                <!-- /Twitter: Tweet:js -->

                                <br/>
                                 <a href="<?php echo $product_page; ?>" target="_blank" title="[new window]">Product Page</a>
                                    |
                                <span><a href="https://github.com/orbisius/orbisius-simple-notice/issues"
                                    target="_blank" title="[new window]">Support</a>
                                </span>
                            </div>
                        </div> <!-- .postbox -->

                        <div class="postbox"> <!-- quick-contact -->
                            <?php
                            $current_user = wp_get_current_user();
                            $email = empty($current_user->user_email) ? '' : $current_user->user_email;
                            $quick_form_action = 'https://apps.orbisius.com/quick-contact/';

                            if (!empty($_SERVER['DEV_ENV'])) {
                                $quick_form_action = 'http://localhost/projects/quick-contact/';
                            }
                            ?>
                            <script>
                                var octc_quick_contact = {
                                    validate_form : function () {
                                        try {
                                            var msg = jQuery('#octc_msg').val().trim();
                                            var email = jQuery('#octc_email').val().trim();

                                            email = email.replace(/\s+/, '');
                                            email = email.replace(/\.+/, '.');
                                            email = email.replace(/\@+/, '@');

                                            if ( msg == '' ) {
                                                alert('Enter your message.');
                                                jQuery('#octc_msg').focus().val(msg).css('border', '1px solid red');
                                                return false;
                                            } else {
                                                // all is good clear borders
                                                jQuery('#octc_msg').css('border', '');
                                            }

                                            if ( email == '' || email.indexOf('@') <= 2 || email.indexOf('.') == -1) {
                                                alert('Enter your email and make sure it is valid.');
                                                jQuery('#octc_email').focus().val(email).css('border', '1px solid red');
                                                return false;
                                            } else {
                                                // all is good clear borders
                                                jQuery('#octc_email').css('border', '');
                                            }

                                            return true;
                                        } catch(e) {};
                                    }
                                };
                            </script>
                            <h3><span>Quick Question or Suggestion</span></h3>
                            <div class="inside">
                                <div>
                                    <form method="post" action="<?php echo $quick_form_action; ?>" target="_blank">
                                        <?php
                                            global $wp_version;
											$plugin_data = get_plugin_data(__FILE__);

                                            $hidden_data = array(
                                                'site_url' => site_url(),
                                                'wp_ver' => $wp_version,
                                                'first_name' => $current_user->first_name,
                                                'last_name' => $current_user->last_name,
                                                'product_name' => $plugin_data['Name'],
                                                'product_ver' => $plugin_data['Version'],
                                                'woocommerce_ver' => defined('WOOCOMMERCE_VERSION') ? WOOCOMMERCE_VERSION : 'n/a',
                                            );
                                            $hid_data = http_build_query($hidden_data);
                                            echo "<input type='hidden' name='data[sys_info]' value='$hid_data' />\n";
                                        ?>
                                        <textarea class="widefat" id='octc_msg' name='data[msg]' required="required"></textarea>
                                        <br/>Your Email: <input type="text" class=""
                                               id="octc_email" name='data[sender_email]' placeholder="Email" required="required"
                                               value="<?php echo esc_attr($email); ?>"
                                               />
                                        <br/><input type="submit" class="button-primary" value="<?php _e('Send Feedback') ?>"
                                                    onclick="return octc_quick_contact.validate_form();" />
                                        <br/>
                                        What data will be sent
                                        <a href='javascript:void(0);'
                                            onclick='jQuery(".octc_data_to_be_sent").toggle();'>(show/hide)</a>
                                        <div class="hide app_hide octc_data_to_be_sent">
                                            <textarea class="widefat" rows="4" readonly="readonly" disabled="disabled"><?php
                                            foreach ($hidden_data as $key => $val) {
                                                if (is_array($val)) {
                                                    $val = var_export($val, 1);
                                                }

                                                echo "$key: $val\n";
                                            }
                                            ?></textarea>
                                        </div>
                                    </form>
                                </div>
                            </div> <!-- .inside -->
                         </div> <!-- .postbox --> <!-- /quick-contact -->

                    </div> <!-- .meta-box-sortables -->

                </div> <!-- #postbox-container-1 .postbox-container sidebar -->

            </div> <!-- #post-body .metabox-holder .columns-2 -->

            <br class="clear">
        </div> <!-- #poststuff -->

        <h2>Extensions</h2>
        <p>Extensions allow you to add an extra functionality to this plugin.</p>
        <div>
            <?php
            if (!has_action('orbisius_simple_notice_ext_action_extension_list')) {
                echo "No extensions have been installed.";
            } else {
	            echo "The following extensions have been found.<br/><ul>";
	            echo "<ul>";
	            do_action('orbisius_simple_notice_ext_action_extension_list');
                echo "</ul>";
            }
            ?>
        </div>

    </div>
    <?php
}

function orbisius_simple_notice_generate_newsletter_box() {
    $current_user = wp_get_current_user();
    $email = empty($current_user->user_email) ? '' : $current_user->user_email;

    $plugin_data = orbisius_simple_notice_get_plugin_data();
    $plugin_name = $plugin_data['name'];
    ?>

    <table>
        <tr>
            <td valign="top">
                <div id='app-plugin-notice' class='app_mailing_list_box' width="100%">
                    <!-- Begin MailChimp Signup Form -->
                    <div id="mc_embed_signup">
                        <form action="https://orbisius.us2.list-manage.com/subscribe/post?u=005070a78d0e52a7b567e96df&amp;id=1b83cd2093" method="post"
                              id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank">
                            <input type="hidden" value="<?php echo esc_attr($plugin_name); ?>" name="SRC" id="mce-SRC" />
                            <input type="hidden" value="<?php echo esc_attr($plugin_name); ?>" name="MERGE3" id="mce-MERGE3" />

                            <p>Join our newsletter and we will be notify you when we release cool plugins.</p>
                            <div class="mc-field-group">
                                <label for="mce-EMAIL">Email <span class="app_asterisk">*</span>
                                </label>
                                <input type="email" value="<?php echo esc_attr($email); ?>" name="EMAIL" class="required email" id="mce-EMAIL" />
                            </div>
                            <div id="mce-responses" class="clear">
                                <div class="response" id="mce-error-response" style="display:none"></div>
                                <div class="response" id="mce-success-response" style="display:none"></div>
                            </div>
                            <div class="clear"><input type="submit" value="Join" name="subscribe" id="mc-embedded-subscribe" class="button button-primary"></div>
                        </form>
                    </div>
                    <!--End mc_embed_signup-->
                </div>
            </td>
            <!--<td valign="top">
                <p>
                    You can also signup using this link: <a href="https://eepurl.com/guNzr" target="_blank">https://eepurl.com/guNzr</a> <br/>
                    You can also signup using this QR code: <br/>
                    <img src="%%PLUGIN_URL%%/zzz_media/qr_code.png" alt="" width="100"/>
                </p>
            </td>-->
        </tr>
    </table>

    <?php
}

function orbisius_simple_notice_get_plugin_data() {
    // pull only these vars
    $default_headers = array(
        'Name' => 'Plugin Name',
        'PluginURI' => 'Plugin URI',
        'Description' => 'Description',
    );

    $plugin_data = get_file_data(__FILE__, $default_headers, 'plugin');

    $data['name'] = $plugin_data['Name'];
    $data['url'] = $plugin_data['PluginURI'];
    $data['description'] = $plugin_data['Description'];

    return $data;
}

/**
 * adds some HTML comments in the page so people would know that this plugin powers their site.
 */
function orbisius_simple_notice_add_plugin_credits() {
    $plugin_data = orbisius_simple_notice_get_plugin_data();

    $url = $plugin_data['url'];
    $name = $plugin_data['name'];

    printf(PHP_EOL . PHP_EOL . '<!-- ' . "Powered by $name | URL: $url " . '-->' . PHP_EOL . PHP_EOL);
}
