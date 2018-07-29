<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2018 osCommerce

  Released under the GNU General Public License
*/

  class d_latest_news {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function __construct() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));
      $this->title = MODULE_ADMIN_DASHBOARD_LATEST_NEWS_TITLE;
      $this->description = MODULE_ADMIN_DASHBOARD_LATEST_NEWS_DESCRIPTION;
      $this->description .= '<div class="alert alert-info">' . MODULE_CONTENT_BOOTSTRAP_ROW_DESCRIPTION . '</div>';

      if ( defined('MODULE_ADMIN_DASHBOARD_LATEST_NEWS_STATUS') ) {
        $this->sort_order = MODULE_ADMIN_DASHBOARD_LATEST_NEWS_SORT_ORDER;
        $this->enabled = (MODULE_ADMIN_DASHBOARD_LATEST_NEWS_STATUS == 'True');
      }
    }

    function execute() {
      global $oscTemplate;
      
      $content_width = MODULE_ADMIN_DASHBOARD_LATEST_NEWS_CONTENT_WIDTH;
      $output = '';
      $output .= '<div class="col-sm-' . $content_width .' ' . strtr($this->code,'_','-') . '">';
       
      if (!class_exists('lastRSS')) {
        include('includes/classes/rss.php');
      }

      $rss = new lastRSS;
      $rss->items_limit = 5;
      $rss->cache_dir = DIR_FS_CACHE;
      $rss->cache_time = 86400;
      $feed = $rss->get('http://feeds.feedburner.com/osCommerceNewsAndBlogs');

      $output .= '<table class="table table-bordered table-striped table-hover">' .
                '   <thead>' .
                '       <tr class="dataTableHeadingRow">' .
                '           <th class="dataTableHeadingContent">' . MODULE_ADMIN_DASHBOARD_LATEST_NEWS_TITLE . '</th>' .
                '           <th class="dataTableHeadingContent" align="right">' . MODULE_ADMIN_DASHBOARD_LATEST_NEWS_DATE . '</th>' .
                '       </tr>' . 
                '   </thead>';

      if (is_array($feed) && !empty($feed)) {
        foreach ($feed['items'] as $item) {
          $output .= '  <tr class="dataTableRow">' .
                     '    <td class="dataTableContent"><a href="' . $item['link'] . '" target="_blank">' . $item['title'] . '</a></td>' .
                     '    <td class="dataTableContent" align="right" style="white-space: nowrap;">' . date("F j, Y", strtotime($item['pubDate'])) . '</td>' .
                     '  </tr>';
        }
      } else {
        $output .= '  <tr class="dataTableRow">' .
                   '    <td class="dataTableContent" colspan="2">' . MODULE_ADMIN_DASHBOARD_LATEST_NEWS_FEED_ERROR . '</td>' .
                   '  </tr>';
      }

      $output .= '  <tr class="dataTableRow">' .
                 '    <td class="dataTableContent" align="right" colspan="2"><a href="http://www.oscommerce.com/Us&News" target="_blank">' . tep_image('images/icon_oscommerce.png', MODULE_ADMIN_DASHBOARD_LATEST_NEWS_ICON_NEWS) . '</a>&nbsp;<a href="http://www.oscommerce.com/newsletter/subscribe" target="_blank">' . tep_image('images/icon_newsletter.png', MODULE_ADMIN_DASHBOARD_LATEST_NEWS_ICON_NEWSLETTER) . '</a>&nbsp;<a href="http://plus.google.com/+osCommerce" target="_blank">' . tep_image('images/icon_google_plus.png', MODULE_ADMIN_DASHBOARD_LATEST_NEWS_ICON_GOOGLE_PLUS) . '</a>&nbsp;<a href="http://www.facebook.com/pages/osCommerce/33387373079" target="_blank">' . tep_image('images/icon_facebook.png', MODULE_ADMIN_DASHBOARD_LATEST_NEWS_ICON_FACEBOOK) . '</a>&nbsp;<a href="http://twitter.com/osCommerce" target="_blank">' . tep_image('images/icon_twitter.png', MODULE_ADMIN_DASHBOARD_LATEST_NEWS_ICON_TWITTER) . '</a>&nbsp;<a href="http://feeds.feedburner.com/osCommerceNewsAndBlogs" target="_blank">' . tep_image('images/icon_rss.png', MODULE_ADMIN_DASHBOARD_LATEST_NEWS_ICON_RSS) . '</a></td>' .
                 '  </tr>' .
                 '</table>';

      $output .= '</div>';

      $oscTemplate->addContent($output, $this->group);
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_ADMIN_DASHBOARD_LATEST_NEWS_STATUS');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Latest News Module', 'MODULE_ADMIN_DASHBOARD_LATEST_NEWS_STATUS', 'True', 'Do you want to show the latest osCommerce News on the dashboard?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_ADMIN_DASHBOARD_LATEST_NEWS_CONTENT_WIDTH', '12', 'What width container should the content be shown in? (12 = full width, 6 = half width).', '6', '2', 'tep_cfg_select_option(array(\'12\', \'11\', \'10\', \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ADMIN_DASHBOARD_LATEST_NEWS_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_ADMIN_DASHBOARD_LATEST_NEWS_STATUS', 'MODULE_ADMIN_DASHBOARD_LATEST_NEWS_CONTENT_WIDTH', 'MODULE_ADMIN_DASHBOARD_LATEST_NEWS_SORT_ORDER');
    }
  }
?>
