<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  $languages = tep_get_languages();
  $languages_array = array();
  $languages_selected = DEFAULT_LANGUAGE;
  for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
    $languages_array[] = array('id' => $languages[$i]['code'],
                               'text' => $languages[$i]['name']);
    if ($languages[$i]['directory'] == $language) {
      $languages_selected = $languages[$i]['code'];
    }
  }

  require('includes/template_top.php');
?>
<div class="page-header">
<?php	
  if (sizeof($languages_array) > 1) {
?>
	<div class="float-right"><?php echo tep_draw_form('adminlanguage', 'index.php', '', 'get') . tep_draw_pull_down_menu('language', $languages_array, $languages_selected, 'onchange="this.form.submit();"') . tep_hide_session_id() . '</form>'; ?></div>
<?php
	}
?>	
    <h1><?php echo STORE_NAME; ?></h1>
</div>
<div class="row">

<?php
    echo $oscTemplate->getContent('dashboard');
?>
</div>

<?php
  require('includes/template_bottom.php');
  require('includes/application_bottom.php');
?>
