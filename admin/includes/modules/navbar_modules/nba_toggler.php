<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2018 osCommerce

  Released under the GNU General Public License
*/

  class nba_toggler {
    var $code;
    var $group = 'navbar_modules_home';
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;    
    
    function __construct() {
      $this->code = get_class($this);  
      $this->title = MODULE_ADMIN_NAVBAR_TOGGLER_TITLE;
      $this->description = MODULE_ADMIN_NAVBAR_TOGGLER_DESCRIPTION;

      if ( defined('MODULE_ADMIN_NAVBAR_TOGGLER_STATUS') ) {
        $this->sort_order = MODULE_ADMIN_NAVBAR_TOGGLER_SORT_ORDER;
        $this->enabled = (MODULE_ADMIN_NAVBAR_TOGGLER_STATUS == 'True');
        
        switch (MODULE_ADMIN_NAVBAR_TOGGLER_CONTENT_PLACEMENT) {
          case 'Right':
          $this->group = 'navbar_modules_right';
          break;
        } 
      }
    }

    function getOutput() {
      global $oscTemplate;
      
      ob_start();
      require('includes/modules/navbar_modules/templates/toggler.php');
      $data = ob_get_clean();

      $oscTemplate->addBlock($data, $this->group);
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_ADMIN_NAVBAR_TOGGLER_STATUS');
    }

    function install() {
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Hamburger Button Module', 'MODULE_ADMIN_NAVBAR_TOGGLER_STATUS', 'True', 'Do you want to add the module to your Navbar?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Placement', 'MODULE_ADMIN_NAVBAR_TOGGLER_CONTENT_PLACEMENT', 'Home', 'This module must be placed in the Right area of the Navbar.', '6', '1', 'tep_cfg_select_option(array(\'Right\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ADMIN_NAVBAR_TOGGLER_SORT_ORDER', '500', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_ADMIN_NAVBAR_TOGGLER_STATUS', 'MODULE_ADMIN_NAVBAR_TOGGLER_CONTENT_PLACEMENT', 'MODULE_ADMIN_NAVBAR_TOGGLER_SORT_ORDER');
    }
  }
  