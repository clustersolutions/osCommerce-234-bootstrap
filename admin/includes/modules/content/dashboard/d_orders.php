<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2018 osCommerce

  Released under the GNU General Public License
*/

  class d_orders {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));
      $this->title = MODULE_ADMIN_DASHBOARD_ORDERS_TITLE;
      $this->description = MODULE_ADMIN_DASHBOARD_ORDERS_DESCRIPTION;
      $this->description .= '<div class="alert alert-info">' . MODULE_CONTENT_BOOTSTRAP_ROW_DESCRIPTION . '</div>';

      if ( defined('MODULE_ADMIN_DASHBOARD_ORDERS_STATUS') ) {
        $this->sort_order = MODULE_ADMIN_DASHBOARD_ORDERS_SORT_ORDER;
        $this->enabled = (MODULE_ADMIN_DASHBOARD_ORDERS_STATUS == 'True');
      }
    }

    function execute() {
      global $oscTemplate, $languages_id;
      
      $content_width = MODULE_ADMIN_DASHBOARD_ORDERS_CONTENT_WIDTH;
      $output = '';
      $output .= '<div class="col-sm-' . $content_width .' ' . strtr($this->code,'_','-') . '">';
 
      $output .= '<table class="table table-bordered table-striped table-hover">' .
                '   <thead>' .
                '       <tr class="dataTableHeadingRow">' .
                '           <th class="dataTableHeadingContent">' . MODULE_ADMIN_DASHBOARD_ORDERS_TITLE . '</th>' .
                '           <th class="dataTableHeadingContent">' . MODULE_ADMIN_DASHBOARD_ORDERS_TOTAL . '</th>' .
                '           <th class="dataTableHeadingContent">' . MODULE_ADMIN_DASHBOARD_ORDERS_DATE . '</th>' .
                '           <th class="dataTableHeadingContent">' . MODULE_ADMIN_DASHBOARD_ORDERS_ORDER_STATUS . '</th>' .
                '       </tr>' . 
                '   </thead>';

      $orders_query = tep_db_query("select o.orders_id, o.customers_name, greatest(o.date_purchased, ifnull(o.last_modified, 0)) as date_last_modified, s.orders_status_name, ot.text as order_total from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_TOTAL . " ot, " . TABLE_ORDERS_STATUS . " s where o.orders_id = ot.orders_id and ot.class = 'ot_total' and o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' order by date_last_modified desc limit 6");
      while ($orders = tep_db_fetch_array($orders_query)) {
        $output .= '  <tr class="dataTableRow">' .
                   '    <td class="dataTableContent"><a href="' . tep_href_link('orders.php', 'oID=' . (int)$orders['orders_id'] . '&action=edit') . '">' . tep_output_string_protected($orders['customers_name']) . '</a></td>' .
                   '    <td class="dataTableContent">' . strip_tags($orders['order_total']) . '</td>' .
                   '    <td class="dataTableContent">' . tep_date_short($orders['date_last_modified']) . '</td>' .
                   '    <td class="dataTableContent">' . $orders['orders_status_name'] . '</td>' .
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
      return defined('MODULE_ADMIN_DASHBOARD_ORDERS_STATUS');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Orders Module', 'MODULE_ADMIN_DASHBOARD_ORDERS_STATUS', 'True', 'Do you want to show the latest orders on the dashboard?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_ADMIN_DASHBOARD_ORDERS_CONTENT_WIDTH', '12', 'What width container should the content be shown in? (12 = full width, 6 = half width).', '6', '2', 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ADMIN_DASHBOARD_ORDERS_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_ADMIN_DASHBOARD_ORDERS_STATUS', 'MODULE_ADMIN_DASHBOARD_ORDERS_CONTENT_WIDTH', 'MODULE_ADMIN_DASHBOARD_ORDERS_SORT_ORDER');
    }
  }
?>
