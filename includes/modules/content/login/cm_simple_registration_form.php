<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class cm_simple_registration_form {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function cm_simple_registration_form() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));

      $this->title = MODULE_CONTENT_SIMPLE_REGISTRATION_FORM_TITLE;
      $this->description = MODULE_CONTENT_SIMPLE_REGISTRATION_FORM_DESCRIPTION;

      if ( defined('MODULE_CONTENT_SIMPLE_REGISTRATION_FORM_STATUS') ) {
        $this->sort_order = MODULE_CONTENT_SIMPLE_REGISTRATION_FORM_SORT_ORDER;
        $this->enabled = (MODULE_CONTENT_SIMPLE_REGISTRATION_FORM_STATUS == 'True');
      }
    }

    function execute() {
      global $HTTP_GET_VARS, $HTTP_POST_VARS, $sessiontoken, $login_customer_id, $messageStack, $oscTemplate;

      $error = false;

      if (isset($HTTP_POST_VARS['action']) && ($HTTP_POST_VARS['action'] == 'process') && isset($HTTP_POST_VARS['formid']) && ($HTTP_POST_VARS['formid'] == $sessiontoken)) {

        $firstname = tep_db_prepare_input($HTTP_POST_VARS['firstname']);
        $lastname = tep_db_prepare_input($HTTP_POST_VARS['lastname']);
        $email_address = tep_db_prepare_input($HTTP_POST_VARS['email_address']);
        $password = tep_db_prepare_input($HTTP_POST_VARS['password']);
        $confirmation = tep_db_prepare_input($HTTP_POST_VARS['confirmation']);
/*
        if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
          $error = true;

          $messageStack->add('login', ENTRY_FIRST_NAME_ERROR);
        }

        if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
          $error = true;
 
          $messageStack->add('login', ENTRY_LAST_NAME_ERROR);
        } 
*/
        if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
          $error = true;

          $messageStack->add('login', ENTRY_EMAIL_ADDRESS_ERROR);
        } elseif (tep_validate_email($email_address) == false) {
          $error = true;

          $messageStack->add('login', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
        } else {
          $check_email_query = tep_db_query("select count(*) as total from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "'");
          $check_email = tep_db_fetch_array($check_email_query);
          if ($check_email['total'] > 0) {
            $error = true;

            $messageStack->add('login', ENTRY_EMAIL_ADDRESS_ERROR_EXISTS);
          }
        }

        if (strlen($password) < ENTRY_PASSWORD_MIN_LENGTH) {
          $error = true;

          $messageStack->add('login', ENTRY_PASSWORD_ERROR);
        } elseif ($password != $confirmation) {
          $error = true;

          $messageStack->add('login', ENTRY_PASSWORD_ERROR_NOT_MATCHING);
        }

        if ($error == false) {
          $sql_data_array = array('customers_firstname' => $firstname,
                                  'customers_lastname' => $lastname,
                                  'customers_email_address' => $email_address,
                                  'customers_telephone' => '',
                                  'customers_newsletter' => 1,
                                  'customers_password' => tep_encrypt_password($password));
    
          tep_db_perform(TABLE_CUSTOMERS, $sql_data_array);

          $customer_id = tep_db_insert_id();
        
          // set $login_customer_id globally and perform post login code in catalog/login.php
          $login_customer_id = (int)$customer_id;

          $sql_data_array = array('customers_id' => $customer_id,
                                  'entry_firstname' => $firstname,
                                  'entry_lastname' => $lastname,
                                  'entry_street_address' => '',
                                  'entry_postcode' => '',
                                  'entry_city' => '',
                                  'entry_telephone' => '',
                                  'entry_fax' => '',
                                  'entry_country_id' => 0);

          tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);

          $address_id = tep_db_insert_id();

          tep_db_query("update " . TABLE_CUSTOMERS . " set customers_default_address_id = '" . (int)$address_id . "' where customers_id = '" . (int)$customer_id . "'");

          tep_db_query("insert into " . TABLE_CUSTOMERS_INFO . " (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created) values ('" . (int)$customer_id . "', '0', now())");

// build the message content
          $name = $firstname . ' ' . $lastname;

          if (ACCOUNT_GENDER == 'true') {
             if ($gender == 'm') {
               $email_text = sprintf(EMAIL_GREET_MR, $lastname);
             } else {
               $email_text = sprintf(EMAIL_GREET_MS, $lastname);
             }
          } else {
            $email_text = sprintf(EMAIL_GREET_NONE, $firstname);
          }

          $email_text .= EMAIL_WELCOME . EMAIL_TEXT . EMAIL_CONTACT . EMAIL_WARNING;
          tep_mail($name, $email_address, EMAIL_SUBJECT, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

        }
      }

      ob_start();
      require(DIR_WS_INCLUDES . 'form_check.js.php');
      include(DIR_WS_MODULES . 'content/' . $this->group . '/templates/simple_registration_form.php');
      $template = ob_get_clean();

      $oscTemplate->addContent($template, $this->group);
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_CONTENT_SIMPLE_REGISTRATION_FORM_STATUS');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Simple Create Account Form Module', 'MODULE_CONTENT_SIMPLE_REGISTRATION_FORM_STATUS', 'True', 'Do you want to enable the login form module?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_CONTENT_SIMPLE_REGISTRATION_FORM_CONTENT_WIDTH', 'Half', 'Should the content be shown in a full or half width container?', '6', '1', 'tep_cfg_select_option(array(\'Full\', \'Half\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_CONTENT_SIMPLE_REGISTRATION_FORM_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_CONTENT_SIMPLE_REGISTRATION_FORM_STATUS', 'MODULE_CONTENT_SIMPLE_REGISTRATION_FORM_CONTENT_WIDTH', 'MODULE_CONTENT_SIMPLE_REGISTRATION_FORM_SORT_ORDER');
    }
  }
?>
