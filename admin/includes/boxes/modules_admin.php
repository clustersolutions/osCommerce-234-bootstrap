<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2018 osCommerce

  Released under the GNU General Public License
*/

  $cl_box_groups[] = array(
    'heading' => BOX_HEADING_ADMIN_MODULES,
    'apps' => array()
  );

  foreach ($cfgAdminModules->getAll() as $m) {
    $cl_box_groups[sizeof($cl_box_groups)-1]['apps'][] = array('code' => 'modules_admin.php',
                                                               'title' => $m['title'],
                                                               'link' => tep_href_link('modules_admin.php', 'set=' . $m['code']));
  }
?>
