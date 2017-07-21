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


require_once('../../../wp-config.php');
require_once(ABSPATH
                . WPINC
                . '/pluggable.php');
require_once(ABSPATH
                . WPINC
                . '/registration.php');

require_once(WP_PLUGIN_DIR
                . '/fbgreeter/functions.php');


//{{{ axiom7_FBgreeter_Login
function axiom7_FBgreeter_Login() {

    if (is_user_logged_in()) {
        echo json_encode(array('success' => TRUE));

        exit(0);
    }

    $appId      = get_option('axiom7_FBgreeter_Facebook_App_ID');
    $appSecret  = get_option('axiom7_FBgreeter_Facebook_App_Secret');

    $facebookCookie = axiom7_FBgreeter_UnpackFacebookCookie($appId,
                                                            $appSecret);
    if ($facebookCookie == NULL) {
        echo json_encode(array('success' => FALSE,
                               'message' => 'Unable to process Facebook cookie.'));

        exit(1);
    }

    $user = json_decode(file_get_contents('https://graph.facebook.com/me?access_token='
                                            . $facebookCookie['access_token']));
    if ($user == NULL) {
        echo json_encode(array('success' => FALSE,
                               'message' => 'Unable to query your Facebook account.'));

        exit(1);
    }

    if (!(isset($user->first_name)
          && isset($user->last_name)
          && isset($user->email))) {
        echo json_encode(array('success' => FALSE,
                               'message' => 'Insufficient permissions to import your Facebook account.'));

        exit(1);
    }

    $username = $user->first_name
                    . '.'
                    . $user->last_name;

    $userId = username_exists($username);
    if ($userId != NULL) {
        wp_set_auth_cookie((int)$userId,
                           TRUE,
                           is_ssl());

        echo json_encode(array('success' => TRUE));

        exit(0);
    }

    $randomPassword = wp_generate_password(12, FALSE);
    $email          = $user->email;

    $userId = wp_create_user($username,
                             $randomPassword,
                             $email);
    if (!is_int($userId)) {
        echo json_encode(array('success' => FALSE,
                               'message' => 'An error ocurred while importing your Facebook account.'));

        exit(1);
    } else {
        $blogName = wp_specialchars_decode(get_option('blogname'),
                                           ENT_QUOTES);

        $message  = sprintf(__('Hello %s!'), $user->first_name)
                        . "\n\n";
        $message .= sprintf(__('We created an account for you at our site %s:'), $blogName)
                        . "\n\n";
        $message .= sprintf(__('Your username is: %s'), $username)
                        . "\n";
        $message .= sprintf(__('Your password is: %s'), $randomPassword)
                        . "\n\n";
        $message .= __('You may use this account to comment on our blog.')
                        . "\n"
                        . __('Of course, you may also log in to our blog using your Facebook account.')
                        . "\n\n"
                        . __('Enjoy!')
                        . "\n\n"
                        . get_option('siteurl');

        @wp_mail($email,
                 sprintf(__('[%s] Welcome!'), $blogName),
                 $message);
    }

    wp_set_auth_cookie((int)$userId,
                       TRUE,
                       is_ssl());

    echo json_encode(array('success' => TRUE));

    exit(0);
}
//}}}


if (!isset($_POST['operation'])
    || ($_POST['operation'] !== 'login')) {
    echo json_encode(array('success' => FALSE,
                           'message' => 'Invalid request.'));

    exit(1);
}

axiom7_FBgreeter_Login();

?>
