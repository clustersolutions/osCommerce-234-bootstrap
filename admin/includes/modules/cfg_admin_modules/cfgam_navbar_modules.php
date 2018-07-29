<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2016 osCommerce

  Released under the GNU General Public License
*/

  class cfgam_navbar_modules {
    var $code = 'navbar_modules';
    var $directory;
    var $language_directory = DIR_FS_ADMIN . 'includes/languages/';
    var $key = 'MODULE_ADMIN_NAVBAR_INSTALLED';
    var $title;
    var $template_integration = false;

    function __construct() {
      $this->directory =  DIR_FS_ADMIN . 'includes/modules/navbar_modules/';
      $this->title = MODULE_CFGA_MODULE_CONTENT_NAVBAR_TITLE;
    }
  }