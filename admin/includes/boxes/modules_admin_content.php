<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2018 osCommerce

  Released under the GNU General Public License
*/

  foreach ( $cl_box_groups as &$group ) {
    if ( $group['heading'] == BOX_HEADING_ADMIN_MODULES ) {
      $group['apps'][] = array('code' => 'modules_admin_content.php',
                               'title' => MODULES_ADMIN_ADMIN_MENU_MODULES_CONTENT,
                               'link' => tep_href_link('modules_admin_content.php'));

      break;
    }
  }
?>
