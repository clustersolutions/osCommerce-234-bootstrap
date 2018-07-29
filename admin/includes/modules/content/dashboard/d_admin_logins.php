<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2018 osCommerce

  Released under the GNU General Public License
*/

  class d_admin_logins {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));
      $this->title = MODULE_ADMIN_DASHBOARD_ADMIN_LOGINS_TITLE;
      $this->description = MODULE_ADMIN_DASHBOARD_ADMIN_LOGINS_DESCRIPTION;
      $this->description .= '<div class="alert alert-info">' . MODULE_CONTENT_BOOTSTRAP_ROW_DESCRIPTION . '</div>';

      if ( defined('MODULE_ADMIN_DASHBOARD_ADMIN_LOGINS_STATUS') ) {
        $this->sort_order = MODULE_ADMIN_DASHBOARD_ADMIN_LOGINS_SORT_ORDER;
        $this->enabled = (MODULE_ADMIN_DASHBOARD_ADMIN_LOGINS_STATUS == 'True');
      }
    }

    function execute() {
      global $oscTemplate;
      
      $content_width = MODULE_ADMIN_DASHBOARD_ADMIN_LOGINS_CONTENT_WIDTH;
      $output = '';
      $output .= '<div class="col-sm-' . $content_width .' ' . strtr($this->code,'_','-') . '">';
       
      $output .= '<table class="table table-bordered table-striped table-hover">' .
                '   <thead>' .
                '       <tr class="dataTableHeadingRow">' .
                '           <th class="dataTableHeadingContent" width="20">&nbsp;</th>' .
                '           <th class="dataTableHeadingContent">' . MODULE_ADMIN_DASHBOARD_ADMIN_LOGINS_TITLE . '</th>' .
                '           <th class="dataTableHeadingContent" align="right">' . MODULE_ADMIN_DASHBOARD_ADMIN_LOGINS_DATE . '</th>' .
                '       </tr>' . 
                '   </thead>';

      $logins_query = tep_db_query("select id, user_name, success, date_added from " . TABLE_ACTION_RECORDER . " where module = 'ar_admin_login' order by date_added desc limit 6");
      while ($logins = tep_db_fetch_array($logins_query)) {
        $output .= '  <tr class="dataTableRow">' .
                   '    <td class="dataTableContent" align="center">' . tep_image('images/icons/' . (($logins['success'] == '1') ? 'tick.gif' : 'cross.gif')) . '</td>' .
                   '    <td class="dataTableContent"><a href="' . tep_href_link('action_recorder.php', 'module=ar_admin_login&aID=' . (int)$logins['id']) . '">' . tep_output_string_protected($logins['user_name']) . '</a></td>' .
                   '    <td class="dataTableContent" align="right">' . tep_date_short($logins['date_added']) . '</td>' .
                   '  </tr>';
      }

      $output .= '</table>';
      $output .= '</div>';

      $oscTemplate->addContent($output, $this->group);
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_ADMIN_DASHBOARD_ADMIN_LOGINS_STATUS');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Administrator Logins Module', 'MODULE_ADMIN_DASHBOARD_ADMIN_LOGINS_STATUS', 'True', 'Do you want to show the latest administrator logins on the dashboard?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_ADMIN_DASHBOARD_ADMIN_LOGINS_CONTENT_WIDTH', '12', 'What width container should the content be shown in? (12 = full width, 6 = half width).', '6', '2', 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ADMIN_DASHBOARD_ADMIN_LOGINS_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_ADMIN_DASHBOARD_ADMIN_LOGINS_STATUS', 'MODULE_ADMIN_DASHBOARD_ADMIN_LOGINS_CONTENT_WIDTH', 'MODULE_ADMIN_DASHBOARD_ADMIN_LOGINS_SORT_ORDER');
    }
  }
?>
