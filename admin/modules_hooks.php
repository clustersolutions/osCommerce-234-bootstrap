<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2016 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  $directory = DIR_FS_CATALOG . 'includes/hooks/';

  require('includes/template_top.php');
?>

<div class="page-header">
	<h1><?php echo HEADING_TITLE; ?></h1>
</div>
<div class="row">
	<div class="col-md-8">	
		<table class="table table-bordered table-striped table-hover">
<?php
  if ( $dir = @dir($directory) ) {
    while ( $file = $dir->read() ) {
      if ( is_dir($directory . '/' . $file) && !in_array($file, array('.', '..')) ) {
?>

			<thead>
			  <tr class="dataTableHeadingRow">
					<th class="dataTableHeadingContent" colspan="2"><?php echo $file; ?></th>
			  </tr>
			</thead>

<?php
        if ( $dir2 = @dir($directory . '/' . $file) ) {
          while ( $file2 = $dir2->read() ) {
            if ( is_dir($directory . '/' . $file . '/' . $file2) && !in_array($file2, array('.', '..')) ) {
              if ( $dir3 = @dir($directory . '/' . $file . '/' . $file2) ) {
                while ( $file3 = $dir3->read() ) {
                  if ( !is_dir($directory . '/' . $file . '/' . $file2 . '/' . $file3) ) {
                    if ( substr($file3, strrpos($file3, '.')) == '.php' ) {
                      $code = substr($file3, 0, strrpos($file3, '.'));
                      $class = 'hook_' . $file . '_' . $file2 . '_' . $code;

                      if ( !class_exists($class) ) {
                        include($directory . '/' . $file . '/' . $file2 . '/' . $file3);
                      }

                      $obj = new $class();

                      foreach ( get_class_methods($obj) as $method ) {
                        if ( substr($method, 0, 7) == 'listen_' ) {
?>
			<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
				<td class="dataTableContent"><?php echo $file2 . '/' . $file3; ?></td>
				<td class="dataTableContent"><?php echo substr($method, 7); ?></td>
			</tr>
<?php
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
?>

		</table>
		<span class="text-muted"><?php echo TEXT_HOOKS_DIRECTORY . ' ' . DIR_FS_CATALOG . 'includes/hooks/'; ?></span>
	</div>
<?php
  echo '</div>';//row end

  require('includes/template_bottom.php');
  require('includes/application_bottom.php');
?>
