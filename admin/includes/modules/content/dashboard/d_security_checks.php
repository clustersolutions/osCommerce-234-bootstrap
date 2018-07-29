<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2018 osCommerce

  Released under the GNU General Public License
*/

  class d_security_checks {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));
      $this->title = MODULE_ADMIN_DASHBOARD_SECURITY_CHECKS_TITLE;
      $this->description = MODULE_ADMIN_DASHBOARD_SECURITY_CHECKS_DESCRIPTION;
      $this->description .= '<div class="alert alert-info">' . MODULE_CONTENT_BOOTSTRAP_ROW_DESCRIPTION . '</div>';

      if ( defined('MODULE_ADMIN_DASHBOARD_SECURITY_CHECKS_STATUS') ) {
        $this->sort_order = MODULE_ADMIN_DASHBOARD_SECURITY_CHECKS_SORT_ORDER;
        $this->enabled = (MODULE_ADMIN_DASHBOARD_SECURITY_CHECKS_STATUS == 'True');
      }
    }

    function execute() {
      global $oscTemplate, $PHP_SELF;

      $content_width = MODULE_ADMIN_DASHBOARD_SECURITY_CHECKS_CONTENT_WIDTH;
      $output = '';
      $output .= '<div class="col-sm-' . $content_width .' ' . strtr($this->code,'_','-') . '">';
 

      $secCheck_types = array('info', 'warning', 'error');
      $secCheck_messages = array();

      $file_extension = substr($PHP_SELF, strrpos($PHP_SELF, '.'));
      $secmodules_array = array();
      if ($secdir = @dir(DIR_FS_ADMIN . 'includes/modules/security_check/')) {
        while ($file = $secdir->read()) {
          if (!is_dir(DIR_FS_ADMIN . 'includes/modules/security_check/' . $file)) {
            if (substr($file, strrpos($file, '.')) == $file_extension) {
              $secmodules_array[] = $file;
            }
          }
        }
        sort($secmodules_array);
        $secdir->close();
      }

      foreach ($secmodules_array as $secmodule) {
        include(DIR_FS_ADMIN . 'includes/modules/security_check/' . $secmodule);

        $secclass = 'securityCheck_' . substr($secmodule, 0, strrpos($secmodule, '.'));
        if (tep_class_exists($secclass)) {
          $secCheck = new $secclass;

          if ( !$secCheck->pass() ) {
            if (!in_array($secCheck->type, $secCheck_types)) {
              $secCheck->type = 'info';
            }

            $secCheck_messages[$secCheck->type][] = $secCheck->getMessage();
          }
        }
      }

      if (isset($secCheck_messages['error'])) {
        $output .= '<div class="alert alert-danger">';

        foreach ($secCheck_messages['error'] as $error) {
          $output .= '<p class="smallText">' . $error . '</p>';
        }

        $output .= '</div>';
      }

      if (isset($secCheck_messages['warning'])) {
        $output .= '<div class="alert alert-warning">';

        foreach ($secCheck_messages['warning'] as $warning) {
          $output .= '<p class="smallText">' . $warning . '</p>';
        }

        $output .= '</div>';
      }

      if (isset($secCheck_messages['info'])) {
        $output .= '<div class="alert alert-info">';

        foreach ($secCheck_messages['info'] as $info) {
          $output .= '<p class="smallText">' . $info . '</p>';
        }

        $output .= '</div>';
      }

      if (empty($secCheck_messages)) {
        $output .= '<div class="alert alert-success"><p class="smallText">' . MODULE_ADMIN_DASHBOARD_SECURITY_CHECKS_SUCCESS . '</p></div>';
      }

      $output .= '</div>';

      $oscTemplate->addContent($output, $this->group);
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_ADMIN_DASHBOARD_SECURITY_CHECKS_STATUS');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Security Checks Module', 'MODULE_ADMIN_DASHBOARD_SECURITY_CHECKS_STATUS', 'True', 'Do you want to run the security checks for this installation?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_ADMIN_DASHBOARD_SECURITY_CHECKS_CONTENT_WIDTH', '12', 'What width container should the content be shown in? (12 = full width, 6 = half width).', '6', '2', 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ADMIN_DASHBOARD_SECURITY_CHECKS_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_ADMIN_DASHBOARD_SECURITY_CHECKS_STATUS', 'MODULE_ADMIN_DASHBOARD_SECURITY_CHECKS_CONTENT_WIDTH', 'MODULE_ADMIN_DASHBOARD_SECURITY_CHECKS_SORT_ORDER');
    }
  }
?>
