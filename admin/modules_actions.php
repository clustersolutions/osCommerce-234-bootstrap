<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2018 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  $directory = DIR_FS_CATALOG . 'includes/actions/';

  require('includes/template_top.php');
?>

<div class="page-header">	
	<h1><?php echo HEADING_TITLE; ?></h1>
</div>
<div class="row">
	<div class="col-md-12">	

		<table class="table table-bordered table-striped table-hover">
			<thead>
				<tr class="dataTableHeadingRow">
					<th class="dataTableHeadingContent"><?php echo TABLE_HEADING_FILE; ?></th>
					<th class="dataTableHeadingContent"><?php echo TABLE_HEADING_ACTION; ?></th>
					<th class="dataTableHeadingContent"><?php echo TABLE_HEADING_CLASS; ?></th>
					<th class="dataTableHeadingContent"><?php echo TABLE_HEADING_METHOD; ?></th>
				</tr>
			</thead>

<?php
  $files = array_diff(scandir($directory), array('.', '..'));
  
  foreach ($files as $file) {
    $code = substr($file, 0, strrpos($file, '.'));
	  $class = 'osC_Actions_' . $code;
    
    if ( !class_exists($class) ) {
      include($directory . '/' . $file);
    }
    
    $obj = new $class();
    
    foreach (get_class_methods($obj) as $method) {
?>
		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
			<td class="dataTableContent"><?php echo $file; ?></td>
			<td class="dataTableContent"><?php echo $code; ?></td>
			<td class="dataTableContent"><?php echo $class; ?></td>
			<td class="dataTableContent"><?php echo $method; ?></td>
		</tr>
<?php
    }
  }
?>

		</table>
		<span class="text-muted"><?php echo TEXT_ACTIONS_DIRECTORY . ' ' . DIR_FS_CATALOG . 'includes/actions/'; ?></span>
	</div>

<?php
  echo '</div>';//row end
  
  require('includes/template_bottom.php');
  require('includes/application_bottom.php');
?>
