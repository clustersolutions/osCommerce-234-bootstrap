<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2018 osCommerce

  Released under the GNU General Public License
*/

  class cma_navbar {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));

      $this->title = MODULE_ADMIN_CONTENT_NAVBAR_TITLE;
      $this->description = MODULE_ADMIN_CONTENT_NAVBAR_DESCRIPTION;

      if ( defined('MODULE_ADMIN_CONTENT_NAVBAR_STATUS') ) {
        $this->sort_order = MODULE_ADMIN_CONTENT_NAVBAR_SORT_ORDER;
        $this->enabled = (MODULE_ADMIN_CONTENT_NAVBAR_STATUS == 'True');
      }
    }

    function execute() {
      global $language, $oscTemplate;
      
      if ( defined('MODULE_ADMIN_NAVBAR_INSTALLED') && tep_not_null(MODULE_ADMIN_NAVBAR_INSTALLED) ) {
        $nav_array = explode(';', MODULE_ADMIN_NAVBAR_INSTALLED);

        $navbar_modules = array();

        foreach ( $nav_array as $nbm ) {
          $class = substr($nbm, 0, strrpos($nbm, '.'));

          if ( !class_exists($class) ) {
            include('includes/languages/' . $language . '/modules/navbar_modules/' . $nbm);
            require('includes/modules/navbar_modules/' . $class . '.php');
          }

          $nav = new $class();

          if ( $nav->isEnabled() ) {
            $navbar_modules[] = $nav->getOutput();
          }
        }
      }
        ob_start();
        include('includes/modules/content/' . $this->group . '/templates/tpl_' . basename(__FILE__));
        $template = ob_get_clean();

        $oscTemplate->addContent($template, $this->group);
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_ADMIN_CONTENT_NAVBAR_STATUS');
    }

    function install() {
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Navbar Module', 'MODULE_ADMIN_CONTENT_NAVBAR_STATUS', 'True', 'Should the Navbar be shown? ', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ADMIN_CONTENT_NAVBAR_SORT_ORDER', '10', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_ADMIN_CONTENT_NAVBAR_STATUS', 'MODULE_ADMIN_CONTENT_NAVBAR_SORT_ORDER');
    }
  }
