<?php
/**
 * COmanage Registry Remote User Authentication Login
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// So we don't have to put the entire app under .htaccess auth, we grab REMOTE_USER
// and stuff it into the session so the auth component knows who we authenticated.

// Since this page isn't part of the framework, we need to reconfigure
// to access the Cake session.

session_name("CAKEPHP");
session_start();

// Set the user

if(empty($_SERVER['REMOTE_USER'])) {

    $query_string = 'target=/registry/auth/login/';

    $forced_reauth_cookie_name = 'registry_forced_reauth_requested';
    if(isset($_COOKIE[$forced_reauth_cookie_name])) {
      if($_COOKIE[$forced_reauth_cookie_name] == '1') {
        $query_string = $query_string . '&forceAuthn=1';

        // Unset the cookie since we have consumed it.
        setcookie($forced_reauth_cookie_name, '', time()-3600, '/', $_SERVER['HTTP_HOST'], true, true);
      }
    }

    $path = '/Shibboleth.sso/Login';
    $location = $path . '?' . $query_string;
    $header = 'Location: ' . $location;

    header($header);

    exit;
}

$_SESSION['Auth']['external']['user'] = $_SERVER['REMOTE_USER'];

header("Location: " . "/registry/users/login");
