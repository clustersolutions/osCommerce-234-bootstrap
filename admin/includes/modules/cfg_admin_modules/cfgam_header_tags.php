<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/

  class cfgam_header_tags {
    var $code = 'header_tags';
    var $directory;
    var $language_directory = DIR_FS_ADMIN . 'includes/languages/';
    var $key = 'MODULE_ADMIN_HEADER_TAGS_INSTALLED';
    var $title;
    var $template_integration = true;

    function __construct() {
      $this->directory =  DIR_FS_ADMIN . 'includes/modules/header_tags/';
      $this->title = MODULE_CFGA_MODULE_HEADER_TAGS_TITLE;
    }
  }
?>
