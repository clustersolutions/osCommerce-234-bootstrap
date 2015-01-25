<?php
/*
  $Id$

  Copyright (c) 2014 Facebook Login module For osCommerce by Cluster Solutions

  Released under the GNU General Public License
*/

  class cm_fb_login_form {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function cm_fb_login_form() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));

      $this->title = MODULE_CONTENT_FB_LOGIN_FORM_TITLE;
      $this->description = MODULE_CONTENT_FB_LOGIN_FORM_DESCRIPTION;

      if ( defined('MODULE_CONTENT_FB_LOGIN_FORM_STATUS') ) {
        $this->sort_order = MODULE_CONTENT_FB_LOGIN_FORM_SORT_ORDER;
        $this->enabled = (MODULE_CONTENT_FB_LOGIN_FORM_STATUS == 'True');
      }
    }

    function execute() {
      global $oscTemplate, $login_customer_id, $messageStack; 

      if (array_key_exists("oauth_provider", $_GET)) {
        $oauth_provider = $_GET['oauth_provider'];
        if ($oauth_provider == 'facebook') {

          require(DIR_WS_CLASSES . 'facebook.php');

          $facebook = new Facebook(array(
                            'appId'  => MODULE_CONTENT_FB_APP_ID,
                            'secret' => MODULE_CONTENT_FB_APP_SECRET,
                          ));

          $user = $facebook->getUser();

          if ($user) {
            try {
            // Proceed knowing you have a logged in user who's authenticated.

              $user_profile = $facebook->api('/me');
              //print_r($facebook->api('me/likes/358784227511031'));
            } catch (FacebookApiException $e) {
              error_log($e);
              $user = null;
            }
          }

          if ($user && tep_not_null($user_profile['email'])) {

            unset($userdata);

            $userdata = $this->checkUser($user_profile['id'], 'facebook', $user_profile['name'] , $user_profile);

            if(isset($userdata["customers_id"])) $login_customer_id = (int)$userdata["customers_id"];

          } elseif ($user && !tep_not_null($user_profile['email'])) {
            $messageStack->add('login', ENTRY_FACEBOOK_LOGIN_EMAIL_PERMISSION_ERROR);
          } else {
            $messageStack->add('login', ENTRY_GNERAL_FACEBOOK_LOGIN_ERROR);
          }
        } 
      } else {
        if(isset($_SESSION['social_login_error'])){
          foreach ($_SESSION['social_login_error']['error'] as $sl_value){
            $messageStack->add('login', $sl_value);
          }
          unset($_SESSION['social_login_error']);
        }
      }

      ob_start();

      echo '
      <div id="fb-root"></div>
      <script>
        function fb_login() {
          loginSubmit(function () {
            document.getElementById(\'status\').innerHTML = \'\';
            window.location.replace(\'' . HTTPS_SERVER . DIR_WS_HTTPS_CATALOG . FILENAME_LOGIN . '?login&oauth_provider=facebook\');
          });
        }

        function loginSubmit(cb,forceAuth) {
          FB.getLoginStatus(function (response) {
            if (response.status !== \'connected\' || forceAuth==true){
              if (forceAuth==true){
                FB.login(function (response) {
                  checkPermissions(cb,false);
                }, {scope: \'email\', auth_type: \'rerequest\'});
              } else {
                FB.login(function (response) {
                  checkPermissions(cb,false);
                }, {scope: \'email, user_friends\'});
              }
            } else {
              checkPermissions(cb);
            }
          });
        }

        function checkPermissions(cb,forceAuth){
          FB.api(
            "/me/permissions",
            function (response) {
              var emailGranted = false;
              for (var i in response.data) {
                var obj = response.data[i];
                if (obj.permission == \'email\' && obj.status == \'granted\') {
                  var emailGranted = true;
                }
              }

              if (emailGranted === false) {
                document.getElementById(\'status\').innerHTML = \'' . MODULE_CONTENT_FB_LOGIN_EMAIL_REQ_ERROR . '\';
                if (forceAuth!=false) loginSubmit(cb,true);
              } else {
                cb();
              }
            }
          );
        }

        window.fbAsyncInit = function() {
          FB.init({
            appId      : \'' . MODULE_CONTENT_FB_APP_ID . '\',
            cookie     : true,  // enable cookies to allow the server to access 
                          // the session
            xfbml      : true,  // parse social plugins on this page
            version    : \'v2.2\' // use version 2.2
          });
          FB.getLoginStatus(function(response) {
            if (response.status === \'connected\') {
              FB.api("/me/picture?width=44&height=44",  function(response) {
                $(\'#fb-profile-pic\').prepend(\'<img src="\' + response.data.url + \'" />\')
              });
            }
          });

        }; 

        // Load the SDK asynchronously
        (function(d, s, id) {
          var js, fjs = d.getElementsByTagName(s)[0];
          if (d.getElementById(id)) return;
          js = d.createElement(s); js.id = id;
          js.src = "//connect.facebook.net/en_US/sdk.js";
          fjs.parentNode.insertBefore(js, fjs);
        }(document, \'script\', \'facebook-jssdk\'));

      </script>
      ';
 
      include(DIR_WS_MODULES . 'content/' . $this->group . '/templates/fb_login_form.php');
      $template = ob_get_clean();

      $oscTemplate->addContent($template, $this->group);


    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_CONTENT_FB_LOGIN_FORM_STATUS');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Login Form Module', 'MODULE_CONTENT_FB_LOGIN_FORM_STATUS', 'True', 'Do you want to enable the login form module?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_CONTENT_FB_LOGIN_FORM_CONTENT_WIDTH', 'Half', 'Should the content be shown in a full or half width container?', '6', '1', 'tep_cfg_select_option(array(\'Full\', \'Half\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_CONTENT_FB_LOGIN_FORM_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('App ID', 'MODULE_CONTENT_FB_APP_ID', '', 'Your Facebook App ID', '6', '1', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Secret', 'MODULE_CONTENT_FB_APP_SECRET', '', 'Your Facebook Secret', '6', '1', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_CONTENT_FB_LOGIN_FORM_STATUS', 'MODULE_CONTENT_FB_LOGIN_FORM_CONTENT_WIDTH', 'MODULE_CONTENT_FB_LOGIN_FORM_SORT_ORDER', 'MODULE_CONTENT_FB_APP_ID', 'MODULE_CONTENT_FB_APP_SECRET');
    }


    function checkUser($uid, $oauth_provider, $username ,$social_data) {
		
      $query = tep_db_query("SELECT customers_id FROM " . TABLE_USERS . " WHERE oauth_uid = '$uid' AND oauth_provider = '$oauth_provider'");

      $result = tep_db_fetch_array($query);

      if (!empty($result)) {
        // User is already present
        return $result;

      } else {
	   		
        $email_address = tep_db_prepare_input($social_data["email"]);

        // Let's only go with email only as name may be different between FB and our record. Matched email but unmatched name case removed. Cluster Solutions. 12-02-14.
        // $get_existing_customer = tep_db_query("select customers_id from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "' and customers_lastname = '" . tep_db_input($social_data["last_name"]) . "' and customers_firstname = '" . tep_db_input($social_data["first_name"]) . "'");

        $get_existing_customer = tep_db_query("SELECT customers_id FROM " . TABLE_CUSTOMERS . " WHERE upper(customers_email_address) = upper('" . tep_db_input($email_address) . "')");

        $get_customer = tep_db_fetch_array($get_existing_customer);

        // user has probably already manually registered and in the database already - lets try and validate this and link it
        if (tep_not_null($get_customer['customers_id'])){ // The found customer validates - lets link him/her to the db
    
          $query = tep_db_query("INSERT INTO " . TABLE_USERS . " (customers_id , oauth_provider, oauth_uid, username) VALUES (" . (int)$get_customer['customers_id'] . ", '$oauth_provider', $uid, '" . $social_data["name"] . "')");

          $query = tep_db_query("SELECT customers_id FROM " . TABLE_USERS . " WHERE oauth_uid = '$uid' and oauth_provider = '$oauth_provider'");

          $result = tep_db_fetch_array($query);

          return $result;

        } else {

          $error = false;

          /* DOB special permission required. Cluster Solutions. 12-02-2014.
          if (ACCOUNT_DOB == 'true') {

	    $dob = tep_db_prepare_input($social_data["birthday"]);

            if ((is_numeric(tep_date_raw_social_logins($dob)) == false) || (@checkdate(substr(tep_date_raw_social_logins($dob), 4, 2), substr(tep_date_raw_social_logins($dob), 6, 2), substr(tep_date_raw_social_logins($dob), 0, 4)) == false)) {

	      $error = true;
	      //$messageStack->add('create_account', ENTRY_DATE_OF_BIRTH_ERROR);
	      $error_stack["error"][]=ENTRY_DATE_OF_BIRTH_ERROR;
	    }
          }
          */
			
          if (ACCOUNT_GENDER == 'true' || 1 == 1) {
            $gender = tep_db_prepare_input(substr($social_data["gender"], 0, 1));
          }
				
          $firstname = tep_db_prepare_input($social_data["first_name"]);
          $lastname = tep_db_prepare_input($social_data["last_name"]);

          /* Take first and last name as it should....email assume to be good from FB. Cluster Solutions. 12-03-14.

          if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
            $error = true;
            //$messageStack->add('create_account', ENTRY_FIRST_NAME_ERROR);
            $error_stack["error"][]=ENTRY_FIRST_NAME_ERROR;
          }
	
          if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
            $error = true;
	
            //$messageStack->add('create_account', ENTRY_LAST_NAME_ERROR);
            $error_stack["error"][]=ENTRY_LAST_NAME_ERROR;
          }
				
          if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
            $error = true;
	
            //$messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR);
            $error_stack["error"][]=ENTRY_EMAIL_ADDRESS_ERROR;
          } elseif (tep_validate_email($email_address) == false) {
            $error = true;
            //$messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
            $error_stack["error"][]=ENTRY_EMAIL_ADDRESS_CHECK_ERROR;
          }
          */				

          if ($error == false) {
            $sql_data_array = array('customers_firstname' => $firstname,
                                    'customers_lastname' => $lastname,
                                    'customers_email_address' => $email_address,
                                    'customers_newsletter' => 1,
                                    'customers_password' => tep_encrypt_password($uid)); // just give it something it won't be used by FB Login, but customer can still have the option of resetting it and using email login as well. Cluster Solutions. 12-03-14.

            if (ACCOUNT_GENDER == 'true' || 1 == 1) $sql_data_array['customers_gender'] = $gender;
            //if (ACCOUNT_DOB == 'true') $sql_data_array['customers_dob'] = tep_date_raw_social_logins($dob);
	    tep_db_perform(TABLE_CUSTOMERS, $sql_data_array);

	    $customer_id = tep_db_insert_id();

            $sql_data_array = array('customers_id' => $customer_id,
                                    'entry_firstname' => $firstname,
                                    'entry_lastname' => $lastname,
                                    'entry_street_address' => '',
                                    'entry_postcode' => '',
                                    'entry_city' => '',
                                    'entry_telephone' => '',
                                    'entry_fax' => '',
                                    'entry_country_id' => 0); // 0 no address info yet, can use -1 to signify no first and last name also. Cluster Solutions 12-03-2014.
  
       	    if (ACCOUNT_GENDER == 'true') $sql_data_array['entry_gender'] = $gender;
            if (ACCOUNT_COMPANY == 'true') $sql_data_array['entry_company'] = '';
	    if (ACCOUNT_SUBURB == 'true') $sql_data_array['entry_suburb'] = '';

	    if (ACCOUNT_STATE == 'true') {
	      $sql_data_array['entry_zone_id'] = '0';
	      $sql_data_array['entry_state'] = '';
            }

            tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);

	    $address_id = tep_db_insert_id();

	    tep_db_query("UPDATE " . TABLE_CUSTOMERS . " SET customers_default_address_id = '" . (int)$address_id . "' WHERE customers_id = '" . (int)$customer_id . "'");
 
            tep_db_query("INSERT INTO " . TABLE_CUSTOMERS_INFO . " (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created) VALUES ('" . (int)$customer_id . "', '0', now())");		  

	    tep_db_query("INSERT INTO " . TABLE_USERS . " (customers_id , oauth_provider, oauth_uid, username) VALUES (" . (int)$customer_id . ",'$oauth_provider', $uid, '" . $social_data["name"] . "')");
	 				
            $query = tep_db_query("SELECT customers_id FROM " . TABLE_USERS . " WHERE oauth_uid = '$uid' AND oauth_provider = '$oauth_provider'");

            $result = tep_db_fetch_array($query);

            return $result;

	  }
        }
      }
    }
  }
?>
