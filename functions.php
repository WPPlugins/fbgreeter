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


//{{{ axiom7_FBgreeter_UnpackFacebookCookie
function axiom7_FBgreeter_UnpackFacebookCookie($facebookAppId,
                                               $facebookAppSecret) {

    if (!isset($_COOKIE['fbs_' . $facebookAppId])) {

        return NULL;
    }

    $parameters = array();

    parse_str(trim($_COOKIE['fbs_' . $facebookAppId], '\\"'),
              $parameters);

    ksort($parameters);

    $payload = '';

    foreach ($parameters as $key => $value) {
        if ($key != 'sig') {
            $payload .= $key . '=' . $value;
        }
    }

    if (md5($payload . $facebookAppSecret) != $parameters['sig']) {

        return NULL;
    }

    return $parameters;
}
//}}}

?>
