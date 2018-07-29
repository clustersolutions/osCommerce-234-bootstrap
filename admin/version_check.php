<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  $current_version = tep_get_version();
  $major_version = (int)substr($current_version, 0, 1);

  $releases = null;
  $new_versions = array();
  $check_message = array();

  if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://www.oscommerce.com/version/online_merchant/' . $major_version);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = trim(curl_exec($ch));
    curl_close($ch);

    if (!empty($response)) {
      $releases = explode("\n", $response);
    }
  } else {
    if ($fp = @fsockopen('www.oscommerce.com', 80, $errno, $errstr, 30)) {
      $header = 'GET /version/online_merchant/' . $major_version . ' HTTP/1.0' . "\r\n" .
                'Host: www.oscommerce.com' . "\r\n" .
                'Connection: close' . "\r\n\r\n";

      fwrite($fp, $header);

      $response = '';
      while (!feof($fp)) {
        $response .= fgets($fp, 1024);
      }

      fclose($fp);

      $response = explode("\r\n\r\n", $response); // split header and content

      if (isset($response[1]) && !empty($response[1])) {
        $releases = explode("\n", trim($response[1]));
      }
    }
  }

  if (is_array($releases) && !empty($releases)) {
    $serialized = serialize($releases);
    if ($f = @fopen(DIR_FS_CACHE . 'oscommerce_version_check.cache', 'w')) {
      fwrite ($f, $serialized, strlen($serialized));
      fclose($f);
    }

    foreach ($releases as $version) {
      $version_array = explode('|', $version);

      if (version_compare($current_version, $version_array[0], '<')) {
        $new_versions[] = $version_array;
      }
    }

    if (!empty($new_versions)) {
      $check_message = array('class' => 'secWarning',
                             'message' => '<div class="alert alert-warning">' . sprintf(VERSION_UPGRADES_AVAILABLE, $new_versions[0][0]) . '</div>');
    } else {
      $check_message = array('class' => 'secSuccess',
                             'message' => '<div class="alert alert-success">' . VERSION_RUNNING_LATEST . '</div>');
    }
  } else {
    $check_message = array('class' => 'secError',
                           'message' => '<div class="alert alert-danger">' . ERROR_COULD_NOT_CONNECT . '</div>');
  }

  require('includes/template_top.php');
?>
<div class="page-header">	
	<h1><?php echo HEADING_TITLE; ?></h1>
</div>
<div class="row">
	<div class="col-md-8">	
		<div class="alert alert-info"><?php echo TITLE_INSTALLED_VERSION . ' <strong>osCommerce Online Merchant v' . $current_version . '</strong>'; ?></div>

		<div class="<?php echo $check_message['class']; ?>">
			<?php echo $check_message['message']; ?>
		</div>
<?php
  if (!empty($new_versions)) {
?>
		<table class="table table-bordered table-striped table-hover">
			<thead>
				<tr class="dataTableHeadingRow">
					<th class="dataTableHeadingContent"><?php echo TABLE_HEADING_VERSION; ?></th>
					<th class="dataTableHeadingContent"><?php echo TABLE_HEADING_RELEASED; ?></th>
					<th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</th>
				</tr>
			</thead>

<?php
    foreach ($new_versions as $version) {
?>
			<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
                <td class="dataTableContent"><?php echo '<a href="' . $version[2] . '" target="_blank">osCommerce Online Merchant v' . $version[0] . '</a>'; ?></td>
                <td class="dataTableContent"><?php echo tep_date_long(substr($version[1], 0, 4) . '-' . substr($version[1], 4, 2) . '-' . substr($version[1], 6, 2)); ?></td>
                <td class="dataTableContent" align="right"><?php echo '<a href="' . $version[2] . '" target="_blank">' . tep_image('images/icon_info.gif', IMAGE_ICON_INFO) . '</a>'; ?>&nbsp;</td>
			</tr>
<?php
    }
?>
		</table>
<?php
  }
?>
	</div>
<?php

  echo '</div>';//row end
  
  require('includes/template_bottom.php');
  require('includes/application_bottom.php');
?>
