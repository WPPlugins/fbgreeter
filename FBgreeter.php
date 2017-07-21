<?php

/*  Copyright 2010 Axiom7 Systems (email: FBgreeter@axiom7.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
Plugin Name: FBgreeter
Plugin URI: http://axiom7.com
Description: This plugin allows Facebook users to login and register to your blog with their Facebook account.
Version: 1.0
Author: Axiom7 Systems (http://axiom7.com)
Author URI: http://axiom7.com
License: GPL2
Text Domain: axiom7_FBgreeter
*/


require_once(WP_PLUGIN_DIR
                . '/fbgreeter/functions.php');


//{{{ Initialization
function axiom7_FBgreeter_Initialize() {

    wp_enqueue_script('facebook-connect',
                      'http://connect.facebook.net/en_US/all.js');
    wp_enqueue_script('jquery');
}

add_action('init', 'axiom7_FBgreeter_Initialize');
//}}}


//{{{ Internationalization
load_plugin_textdomain('axiom7_FBgreeter',
                       NULL,
                       dirname(plugin_basename(__FILE__)));
//}}}


//{{{ Options Management
function axiom7_FBgreeter_OutputOptionsEditingPage() {

    $title          = __('FBgreeter Settings', 'axiom7_FBgreeter');
    $appIdLabel     = __('Your Facebook App ID:', 'axiom7_FBgreeter');
    $appId          = get_option('axiom7_FBgreeter_Facebook_App_ID');
    $appSecretLabel = __('Your Facebook App Secret:', 'axiom7_FBgreeter');
    $appSecret      = get_option('axiom7_FBgreeter_Facebook_App_Secret');
    $buttonLabel    = __('Save Changes', 'axiom7_FBgreeter');

    $optionsEditingPage = <<<END
<div class="wrap">
    <h2>{$title}</h2>
    <form action="" method="post">
        <p>{$appIdLabel}
           <input name="axiom7_FBgreeter_Facebook_App_ID" size="32" type="text" value="{$appId}" /></p>
        <p>{$appSecretLabel}
           <input name="axiom7_FBgreeter_Facebook_App_Secret" size="32" type="text" value="{$appSecret}" /></p>
        <p class="submit">
            <input class="button-primary" type="submit" value="{$buttonLabel}" />
        </p>
    </form>
</div>
END;

    echo $optionsEditingPage;
}

function axiom7_FBgreeter_OutputUpdateConfirmation() {

    $message = __('Settings saved.', 'axiom7_FBgreeter');

    $updateConfirmation = <<<END
<div class="updated">
    <p><strong>{$message}</strong></p>
</div>
END;

    echo $updateConfirmation;
}

function axiom7_FBgreeter_OutputOptions() {

    if (!current_user_can('manage_options')) {

        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (isset($_POST['axiom7_FBgreeter_Facebook_App_ID'])
            && isset($_POST['axiom7_FBgreeter_Facebook_App_Secret'])) {
        $appId      = $_POST['axiom7_FBgreeter_Facebook_App_ID'];
        $appSecret  = $_POST['axiom7_FBgreeter_Facebook_App_Secret'];

        update_option('axiom7_FBgreeter_Facebook_App_ID', $appId);
        update_option('axiom7_FBgreeter_Facebook_App_Secret', $appSecret);

        axiom7_FBgreeter_OutputUpdateConfirmation();
    }

    axiom7_FBgreeter_OutputOptionsEditingPage();
}

function axiom7_FBgreeter_AddOptionPage() {

  add_options_page('FBgreeter Settings',
                   'FBgreeter',
                   'manage_options',
                   'axiom7_FBgreeter',
                   'axiom7_FBgreeter_OutputOptions');
}

add_action('admin_menu',
           'axiom7_FBgreeter_AddOptionPage');
//}}}


//{{{ FBgreeter Widget
class axiom7_FBgreeterWidget extends WP_Widget {

    //{{{ __construct
    function __construct() {

        parent::__construct(FALSE,
                            $name = "axiom7&#39;s FBgreeter");
    }
    //}}}


    //{{{ widget
    function widget($parameters,
                    $instance) {

        extract($parameters);

        $title = apply_filters('widget_title',
                               $instance['title']);

        if (!empty($title)) {
            $fbGreeterWidget = <<<END
{$before_widget}
{$before_title}{$title}{$after_title}
END;
        } else {
            $fbGreeterWidget = <<<END
{$before_widget}
END;
        }

        $appId      = get_option('axiom7_FBgreeter_Facebook_App_ID');
        $appSecret  = get_option('axiom7_FBgreeter_Facebook_App_Secret');

        $facebookCookie = axiom7_FBgreeter_UnpackFacebookCookie($appId,
                                                                $appSecret);
        if ($facebookCookie == NULL) {
            $fbGreeterWidget .= <<<END
<fb:login-button perms="email,user_birthday">Log In</fb:login-button>
END;
        } else {
            $fbGreeterWidget .= <<<END
<fb:login-button autologoutlink="true">Log Out</fb:login-button>
END;
        }

        $ajaxHandlerUrl = get_bloginfo('wpurl')
                            . '/wp-content/plugins/fbgreeter/Ajax_handler.php';

        $fbGreeterWidget .= <<<END
<div id="fb-root"></div>
<script type="text/javascript">
    FB.init({ appId: '{$appId}', status: true, cookie: true, xfbml:  true });

    FB.Event.subscribe('auth.login',    function(response) {
                                            jQuery.ajax({
                                                            async: false,
                                                            data: ({ operation: "login" }),
                                                            dataType: "json",
                                                            global: false,
                                                            success: function(reply) {
                                                                if (!reply.success) {
                                                                    alert(reply.message);
                                                                }
                                                            },
                                                            type: "POST",
                                                            url: "{$ajaxHandlerUrl}"
                                                        });

                                            window.location.reload();
                                        });
    FB.Event.subscribe('auth.logout',   function(response) {
                                            window.location.reload();
                                        });
</script>
{$after_widget}
END;

        echo $fbGreeterWidget;
    }
    //}}}

    //{{{ update
    function update($newInstance,
                    $oldInstance) {

        $instance = $oldInstance;

        $instance['title'] = strip_tags($newInstance['title']);

        return $instance;
    }
    //}}}

    //{{{ form
    function form($instance) {

        $title              = esc_attr($instance['title']);
        $titleFieldId       = $this->get_field_id('title');
        $titleFieldLabel    = __('Title:');
        $titleFieldName     = $this->get_field_name('title');

        $formFragment = <<<END
<p><label for="{$titleFieldId}">{$titleFieldLabel} <input class="widefat" id="{$titleFieldId}" name="{$titleFieldName}" type="text" value="{$title}" /></label></p>
END;

        echo $formFragment;
    }
    //}}}

}

function axiom7_FBgreeter_RegisterWidget() {

    register_widget('axiom7_FBgreeterWidget');
}

add_action('widgets_init', 'axiom7_FBgreeter_RegisterWidget');
//}}}

?>
