<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/

  class cfgam_boxes {
    var $code = 'boxes';
    var $directory;
    var $language_directory = DIR_FS_ADMIN . 'includes/languages/';
    var $key = 'MODULE_ADMIN_BOXES_INSTALLED';
    var $title;
    var $template_integration = true;

    function __construct() {
      $this->directory = DIR_FS_ADMIN . 'includes/modules/boxes/';
      $this->title = MODULE_CFGA_MODULE_BOXES_TITLE;
    }
  }
?>
