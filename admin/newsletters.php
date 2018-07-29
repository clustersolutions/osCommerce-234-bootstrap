<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  $action = (isset($_GET['action']) ? $_GET['action'] : '');

  if (tep_not_null($action)) {
    switch ($action) {
      case 'lock':
      case 'unlock':
        $newsletter_id = tep_db_prepare_input($_GET['nID']);
        $status = (($action == 'lock') ? '1' : '0');

        tep_db_query("update " . TABLE_NEWSLETTERS . " set locked = '" . $status . "' where newsletters_id = '" . (int)$newsletter_id . "'");

        tep_redirect(tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $_GET['nID']));
        break;
      case 'insert':
      case 'update':
        if (isset($_POST['newsletter_id'])) $newsletter_id = tep_db_prepare_input($_POST['newsletter_id']);
        $newsletter_module = tep_db_prepare_input($_POST['module']);
        
        $allowed = array_map(function($v) {return basename($v, '.php');}, glob('includes/modules/newsletters/*.php'));
        if (!in_array($newsletter_module, $allowed)) {
          $messageStack->add(ERROR_NEWSLETTER_MODULE_NOT_EXISTS, 'error');
          $newsletter_error = true;
        }
        
        $title = tep_db_prepare_input($_POST['title']);
        $content = tep_db_prepare_input($_POST['content']);

        $newsletter_error = false;
        if (empty($title)) {
          $messageStack->add(ERROR_NEWSLETTER_TITLE, 'error');
          $newsletter_error = true;
        }

        if (empty($newsletter_module)) {
          $messageStack->add(ERROR_NEWSLETTER_MODULE, 'error');
          $newsletter_error = true;
        }

        if ($newsletter_error == false) {
          $sql_data_array = array('title' => $title,
                                  'content' => $content,
                                  'module' => $newsletter_module);

          if ($action == 'insert') {
            $sql_data_array['date_added'] = 'now()';
            $sql_data_array['status'] = '0';
            $sql_data_array['locked'] = '0';

            tep_db_perform(TABLE_NEWSLETTERS, $sql_data_array);
            $newsletter_id = tep_db_insert_id();
          } elseif ($action == 'update') {
            tep_db_perform(TABLE_NEWSLETTERS, $sql_data_array, 'update', "newsletters_id = '" . (int)$newsletter_id . "'");
          }

          tep_redirect(tep_href_link('newsletters.php', (isset($_GET['page']) ? 'page=' . $_GET['page'] . '&' : '') . 'nID=' . $newsletter_id));
        } else {
          $action = 'new';
        }
        break;
      case 'deleteconfirm':
        $newsletter_id = tep_db_prepare_input($_GET['nID']);

        tep_db_query("delete from " . TABLE_NEWSLETTERS . " where newsletters_id = '" . (int)$newsletter_id . "'");

        tep_redirect(tep_href_link('newsletters.php', 'page=' . $_GET['page']));
        break;
      case 'delete':
      case 'new': if (!isset($_GET['nID'])) break;
      case 'send':
      case 'confirm_send':
        $newsletter_id = tep_db_prepare_input($_GET['nID']);

        $check_query = tep_db_query("select locked from " . TABLE_NEWSLETTERS . " where newsletters_id = '" . (int)$newsletter_id . "'");
        $check = tep_db_fetch_array($check_query);

        if ($check['locked'] < 1) {
          switch ($action) {
            case 'delete': $error = ERROR_REMOVE_UNLOCKED_NEWSLETTER; break;
            case 'new': $error = ERROR_EDIT_UNLOCKED_NEWSLETTER; break;
            case 'send': $error = ERROR_SEND_UNLOCKED_NEWSLETTER; break;
            case 'confirm_send': $error = ERROR_SEND_UNLOCKED_NEWSLETTER; break;
          }

          $messageStack->add_session($error, 'error');

          tep_redirect(tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $_GET['nID']));
        }
        break;
    }
  }

  require('includes/template_top.php');
?>
<div class="page-header">
<?php	
	if (empty($action)) {
?>
	<div class="float-right"><?php echo tep_draw_button(IMAGE_NEW_NEWSLETTER, 'plus', tep_href_link('newsletters.php', 'action=new')); ?></div>
<?php
	}
?>
	<h1><?php echo HEADING_TITLE; ?></h1>
</div>
<div class="row">
<?php
  if ($action == 'new') {
    $form_action = 'insert';

    $parameters = array('title' => '',
                        'content' => '',
                        'module' => '');

    $nInfo = new objectInfo($parameters);

    if (isset($_GET['nID'])) {
      $form_action = 'update';

      $nID = tep_db_prepare_input($_GET['nID']);

      $newsletter_query = tep_db_query("select title, content, module from " . TABLE_NEWSLETTERS . " where newsletters_id = '" . (int)$nID . "'");
      $newsletter = tep_db_fetch_array($newsletter_query);

      $nInfo->objectInfo($newsletter);
    } elseif ($_POST) {
      $nInfo->objectInfo($_POST);
    }

    $file_extension = substr($PHP_SELF, strrpos($PHP_SELF, '.'));
    $directory_array = array();
    if ($dir = dir('includes/modules/newsletters/')) {
      while ($file = $dir->read()) {
        if (!is_dir('includes/modules/newsletters/' . $file)) {
          if (substr($file, strrpos($file, '.')) == $file_extension) {
            $directory_array[] = $file;
          }
        }
      }
      sort($directory_array);
      $dir->close();
    }

    for ($i=0, $n=sizeof($directory_array); $i<$n; $i++) {
      $modules_array[] = array('id' => substr($directory_array[$i], 0, strrpos($directory_array[$i], '.')), 'text' => substr($directory_array[$i], 0, strrpos($directory_array[$i], '.')));
    }
?>
	<div class="col-md-8">	
		<?php echo tep_draw_form('newsletter', 'newsletters.php', (isset($_GET['page']) ? 'page=' . $_GET['page'] . '&' : '') . 'action=' . $form_action); if ($form_action == 'update') echo tep_draw_hidden_field('newsletter_id', $nID); ?>
			<table class="table table-bordered">
				<tr>
					<td class="main"><?php echo TEXT_NEWSLETTER_MODULE; ?></td>
					<td class="main"><?php echo tep_draw_pull_down_menu('module', $modules_array, $nInfo->module); ?></td>
				</tr>
				<tr>
					<td class="main"><?php echo TEXT_NEWSLETTER_TITLE; ?></td>
					<td class="main"><?php echo tep_draw_input_field('title', $nInfo->title, '', true); ?></td>
				</tr>
				<tr>
					<td class="main" valign="top"><?php echo TEXT_NEWSLETTER_CONTENT; ?></td>
					<td class="main"><?php echo tep_draw_textarea_field('content', 'soft', '100%', '20', $nInfo->content); ?></td>
				</tr>
			</table>

			<nav>
				<ul class="pagination float-right"><?php echo tep_draw_button(IMAGE_SAVE, 'disk', null, 'primary') . ' ' . tep_draw_button(IMAGE_CANCEL, 'close', tep_href_link('newsletters.php', (isset($_GET['page']) ? 'page=' . $_GET['page'] . '&' : '') . (isset($_GET['nID']) ? 'nID=' . $_GET['nID'] : ''))); ?></ul>
			</nav>
		</form>
	</div>
<?php
  } elseif ($action == 'preview') {
    $nID = tep_db_prepare_input($_GET['nID']);

    $newsletter_query = tep_db_query("select title, content, module from " . TABLE_NEWSLETTERS . " where newsletters_id = '" . (int)$nID . "'");
    $newsletter = tep_db_fetch_array($newsletter_query);

    $nInfo = new objectInfo($newsletter);
?>
	<div class="col-md-8">	
		<table class="table table-bordered">
			<tr>
				<td><tt><?php echo nl2br($nInfo->content); ?></tt></td>
			</tr>
		</table>
		<nav>
			<ul class="pagination float-left"><?php echo tep_draw_button(IMAGE_BACK, 'triangle-1-w', tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $_GET['nID'])); ?></ul>
		</nav>
	</div>
<?php
  } elseif ($action == 'send') {
    $nID = tep_db_prepare_input($_GET['nID']);

    $newsletter_query = tep_db_query("select title, content, module from " . TABLE_NEWSLETTERS . " where newsletters_id = '" . (int)$nID . "'");
    $newsletter = tep_db_fetch_array($newsletter_query);

    $nInfo = new objectInfo($newsletter);

    include('includes/languages/' . $language . '/modules/newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));
    include('includes/modules/newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));
    $module_name = $nInfo->module;
    $module = new $module_name($nInfo->title, $nInfo->content);
?>
	<div class="col-md-8">	
		<?php if ($module->show_choose_audience) { echo $module->choose_audience(); } else { echo $module->confirm(); } ?>
	</div>
<?php
  } elseif ($action == 'confirm') {
    $nID = tep_db_prepare_input($_GET['nID']);

    $newsletter_query = tep_db_query("select title, content, module from " . TABLE_NEWSLETTERS . " where newsletters_id = '" . (int)$nID . "'");
    $newsletter = tep_db_fetch_array($newsletter_query);

    $nInfo = new objectInfo($newsletter);

    include('includes/languages/' . $language . '/modules/newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));
    include('includes/modules/newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));
    $module_name = $nInfo->module;
    $module = new $module_name($nInfo->title, $nInfo->content);
?>
	<div class="col-md-8">	
		<?php echo $module->confirm(); ?>
	</div>
<?php
  } elseif ($action == 'confirm_send') {
    $nID = tep_db_prepare_input($_GET['nID']);

    $newsletter_query = tep_db_query("select newsletters_id, title, content, module from " . TABLE_NEWSLETTERS . " where newsletters_id = '" . (int)$nID . "'");
    $newsletter = tep_db_fetch_array($newsletter_query);

    $nInfo = new objectInfo($newsletter);

    include('includes/languages/' . $language . '/modules/newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));
    include('includes/modules/newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));
    $module_name = $nInfo->module;
    $module = new $module_name($nInfo->title, $nInfo->content);
?>
	<div class="col-md-8">	
		<table class="table table-bordered">
			<tr>
				<td class="main" valign="middle"><?php echo tep_image('images/ani_send_email.gif', IMAGE_ANI_SEND_EMAIL); ?></td>
				<td class="main" valign="middle"><strong><?php echo TEXT_PLEASE_WAIT; ?></strong></td>
			</tr>

<?php
  tep_set_time_limit(0);
  flush();
  $module->send($nInfo->newsletters_id);
?>
        </table>
		<span class="text-muted"><font color="#ff0000"><strong><?php echo TEXT_FINISHED_SENDING_EMAILS; ?></strong></font></span>
		<nav>
			<ul class="pagination float-right"><?php echo tep_draw_button(IMAGE_BACK, 'triangle-1-w', tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $_GET['nID'])); ?></ul>
		</nav>		
	</div>	  
<?php
  } else {
?>
	<div class="col-md-8">	
		<table class="table table-bordered table-striped table-hover">
			<thead>
				<tr class="dataTableHeadingRow">
					<th class="dataTableHeadingContent"><?php echo TABLE_HEADING_NEWSLETTERS; ?></th>
					<th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_SIZE; ?></th>
					<th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_MODULE; ?></th>
					<th class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_SENT; ?></th>
					<th class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_STATUS; ?></th>
					<th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</th>
				</tr>
			</thead>
<?php
    $newsletters_query_raw = "select newsletters_id, title, length(content) as content_length, module, date_added, date_sent, status, locked from " . TABLE_NEWSLETTERS . " order by date_added desc";
    $newsletters_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $newsletters_query_raw, $newsletters_query_numrows);
    $newsletters_query = tep_db_query($newsletters_query_raw);
    while ($newsletters = tep_db_fetch_array($newsletters_query)) {
    if ((!isset($_GET['nID']) || (isset($_GET['nID']) && ($_GET['nID'] == $newsletters['newsletters_id']))) && !isset($nInfo) && (substr($action, 0, 3) != 'new')) {
        $nInfo = new objectInfo($newsletters);
      }

      if (isset($nInfo) && is_object($nInfo) && ($newsletters['newsletters_id'] == $nInfo->newsletters_id) ) {
        echo '<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $nInfo->newsletters_id . '&action=preview') . '\'">' . "\n";
      } else {
        echo '<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $newsletters['newsletters_id']) . '\'">' . "\n";
      }
?>
                <td class="dataTableContent"><?php echo '<a href="' . tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $newsletters['newsletters_id'] . '&action=preview') . '">' . tep_image('images/icons/preview.gif', ICON_PREVIEW) . '</a>&nbsp;' . $newsletters['title']; ?></td>
                <td class="dataTableContent" align="right"><?php echo number_format($newsletters['content_length']) . ' bytes'; ?></td>
                <td class="dataTableContent" align="right"><?php echo $newsletters['module']; ?></td>
                <td class="dataTableContent" align="center"><?php if ($newsletters['status'] == '1') { echo tep_image('images/icons/tick.gif', ICON_TICK); } else { echo tep_image('images/icons/cross.gif', ICON_CROSS); } ?></td>
                <td class="dataTableContent" align="center"><?php if ($newsletters['locked'] > 0) { echo tep_image('images/icons/locked.gif', ICON_LOCKED); } else { echo tep_image('images/icons/unlocked.gif', ICON_UNLOCKED); } ?></td>
                <td class="dataTableContent" align="right"><?php if (isset($nInfo) && is_object($nInfo) && ($newsletters['newsletters_id'] == $nInfo->newsletters_id) ) { echo tep_image('images/icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $newsletters['newsletters_id']) . '">' . tep_image('images/icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
			</tr>
<?php
    }
?>
		</table>
		<nav>
			<ul class="pagination float-left"><?php echo $newsletters_split->display_count($newsletters_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_NEWSLETTERS); ?></ul>
            <?php echo $newsletters_split->display_links($newsletters_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></td>
		</nav>
	</div>
<?php
  $heading = array();
  $contents = array();

  switch ($action) {
    case 'delete':
      $heading[] = array('text' => '<strong>' . $nInfo->title . '</strong>');

      $contents = array('form' => tep_draw_form('newsletters', 'newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $nInfo->newsletters_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_INFO_DELETE_INTRO);
      $contents[] = array('text' => '<br /><strong>' . $nInfo->title . '</strong>');
      $contents[] = array('align' => 'center', 'text' => '<br />' . tep_draw_button(IMAGE_DELETE, 'trash', null, 'primary') . tep_draw_button(IMAGE_CANCEL, 'close', tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $_GET['nID'])));
      break;
    default:
      if (isset($nInfo) && is_object($nInfo)) {
        $heading[] = array('text' => '<strong>' . $nInfo->title . '</strong>');

        if ($nInfo->locked > 0) {
          $contents[] = array('align' => 'center', 'text' => tep_draw_button(IMAGE_EDIT, 'document', tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $nInfo->newsletters_id . '&action=new')) . tep_draw_button(IMAGE_DELETE, 'trash', tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $nInfo->newsletters_id . '&action=delete')) . tep_draw_button(IMAGE_PREVIEW, 'document', tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $nInfo->newsletters_id . '&action=preview')) . tep_draw_button(IMAGE_SEND, 'mail-closed', tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $nInfo->newsletters_id . '&action=send')) . tep_draw_button(IMAGE_UNLOCK, 'unlocked', tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $nInfo->newsletters_id . '&action=unlock')));
        } else {
          $contents[] = array('align' => 'center', 'text' => tep_draw_button(IMAGE_PREVIEW, 'document', tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $nInfo->newsletters_id . '&action=preview')) . tep_draw_button(IMAGE_LOCK, 'locked', tep_href_link('newsletters.php', 'page=' . $_GET['page'] . '&nID=' . $nInfo->newsletters_id . '&action=lock')));
        }
        $contents[] = array('text' => '<br />' . TEXT_NEWSLETTER_DATE_ADDED . ' ' . tep_date_short($nInfo->date_added));
        if ($nInfo->status == '1') $contents[] = array('text' => TEXT_NEWSLETTER_DATE_SENT . ' ' . tep_date_short($nInfo->date_sent));
      }
      break;
  }

	if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
		echo '<div class="col-md-4" >' . "\n";

		$box = new box;
		echo $box->infoBox($heading, $contents);

		echo '</div>' . "\n";
	}
  }
  echo '</div>';//row end
  require('includes/template_bottom.php');
  require('includes/application_bottom.php');
?>
