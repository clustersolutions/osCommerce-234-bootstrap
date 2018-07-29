<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  require('includes/classes/currencies.php');
  $currencies = new currencies();

  $orders_statuses = array();
  $orders_status_array = array();
  $orders_status_query = tep_db_query("select orders_status_id, orders_status_name from " . TABLE_ORDERS_STATUS . " where language_id = '" . (int)$languages_id . "'");
  while ($orders_status = tep_db_fetch_array($orders_status_query)) {
    $orders_statuses[] = array('id' => $orders_status['orders_status_id'],
                               'text' => $orders_status['orders_status_name']);
    $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
  }

  $action = (isset($_GET['action']) ? $_GET['action'] : '');

  if (tep_not_null($action)) {
    switch ($action) {
      case 'update_order':
        $oID = tep_db_prepare_input($_GET['oID']);
        $status = tep_db_prepare_input($_POST['status']);
        $comments = tep_db_prepare_input($_POST['comments']);

        $order_updated = false;
        $check_status_query = tep_db_query("select customers_name, customers_email_address, orders_status, date_purchased from " . TABLE_ORDERS . " where orders_id = '" . (int)$oID . "'");
        $check_status = tep_db_fetch_array($check_status_query);

        if ( ($check_status['orders_status'] != $status) || tep_not_null($comments)) {
          tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . tep_db_input($status) . "', last_modified = now() where orders_id = '" . (int)$oID . "'");

          $customer_notified = '0';
          if (isset($_POST['notify']) && ($_POST['notify'] == 'on')) {
            $notify_comments = '';
            if (isset($_POST['notify_comments']) && ($_POST['notify_comments'] == 'on')) {
              $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments) . "\n\n";
            }

            $email = STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link('account_history_info.php', 'order_id=' . $oID, 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status]);

            tep_mail($check_status['customers_name'], $check_status['customers_email_address'], EMAIL_TEXT_SUBJECT, $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

            $customer_notified = '1';
          }

          tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) values ('" . (int)$oID . "', '" . tep_db_input($status) . "', now(), '" . tep_db_input($customer_notified) . "', '" . tep_db_input($comments)  . "')");

          $order_updated = true;
        }

        if ($order_updated == true) {
         $messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');
        } else {
          $messageStack->add_session(WARNING_ORDER_NOT_UPDATED, 'warning');
        }

        tep_redirect(tep_href_link('orders.php', tep_get_all_get_params(array('action')) . 'action=edit'));
        break;
      case 'deleteconfirm':
        $oID = tep_db_prepare_input($_GET['oID']);

        tep_remove_order($oID, $_POST['restock']);

        tep_redirect(tep_href_link('orders.php', tep_get_all_get_params(array('oID', 'action'))));
        break;
    }
  }

  if (($action == 'edit') && isset($_GET['oID'])) {
    $oID = tep_db_prepare_input($_GET['oID']);

    $orders_query = tep_db_query("select orders_id from " . TABLE_ORDERS . " where orders_id = '" . (int)$oID . "'");
    $order_exists = true;
    if (!tep_db_num_rows($orders_query)) {
      $order_exists = false;
      $messageStack->add(sprintf(ERROR_ORDER_DOES_NOT_EXIST, $oID), 'error');
    }
  }
  
  include('includes/classes/order.php');

  $OSCOM_Hooks->call('orders', 'orderAction');

  require('includes/template_top.php');

  $base_url = ($request_type == 'SSL') ? HTTPS_SERVER . DIR_WS_HTTPS_ADMIN : HTTP_SERVER . DIR_WS_ADMIN;
?>

<?php
  if (($action == 'edit') && ($order_exists == true)) {
    $order = new order($oID);
?>
  <div class="page-header">
    <h1><?php echo HEADING_TITLE . ': #' . (int)$oID . ' (' . $order->info['total'] . ')'; ?></h1>
  </div>
  <ul class="nav nav-tabs"  id="orderTabs" role="tablist">
    <li class="nav-item"><?php echo '<a class="nav-link active" id="section_summary_tab" data-toggle="tab" href="#section_summary_content" role="tab" aria-controls="' . TAB_TITLE_SUMMARY . '" aria-selected="true">' . TAB_TITLE_SUMMARY . '</a>'; ?></li>
    <li class="nav-item"><?php echo '<a class="nav-link" id="section_products_tab" data-toggle="tab" href="#section_products_content" role="tab" aria-controls="' . TAB_TITLE_PRODUCTS . '" aria-selected="false">' . TAB_TITLE_PRODUCTS . '</a>'; ?></li>
    <li class="nav-item"><?php echo '<a class="nav-link" id="section_status_history_tab" data-toggle="tab" href="#section_status_history_content" role="tab" aria-controls="' . TAB_TITLE_STATUS_HISTORY . '" aria-selected="false">' . TAB_TITLE_STATUS_HISTORY . '</a>'; ?></li>
  </ul>
  <!-- Tab panes -->
  <div class="tab-content">
  <div role="tabpanel" class="tab-pane active" role="tabpanel" aria-labelledby="section_summary_tab" id="section_summary_content">
    <table class="table">
      <tr>
        <td width="33%" valign="top">
          <fieldset style="border: 0; height: 100%;">
            <legend style="font-weight: bold;"><?php echo ENTRY_CUSTOMER; ?></legend>

            <p><?php echo tep_address_format($order->customer['format_id'], $order->customer, 1, '', '<br />'); ?></p>
            <p><?php echo $order->customer['telephone'] . '<br />' . '<a href="mailto:' . $order->customer['email_address'] . '"><u>' . $order->customer['email_address'] . '</u></a>'; ?></p>
          </fieldset>
        </td>
        <td width="33%" valign="top">
          <fieldset style="border: 0; height: 100%;">
            <legend style="font-weight: bold;"><?php echo ENTRY_SHIPPING_ADDRESS; ?></legend>

            <p><?php echo tep_address_format($order->delivery['format_id'], $order->delivery, 1, '', '<br />'); ?></p>
          </fieldset>
        </td>
        <td width="33%" valign="top">
          <fieldset style="border: 0; height: 100%;">
            <legend style="font-weight: bold;"><?php echo ENTRY_BILLING_ADDRESS; ?></legend>

            <p><?php echo tep_address_format($order->billing['format_id'], $order->billing, 1, '', '<br />'); ?></p>
          </fieldset>
        </td>
      </tr>
      <tr>
        <td width="33%" valign="top">
          <fieldset style="border: 0; height: 100%;">
            <legend style="font-weight: bold;"><?php echo ENTRY_PAYMENT_METHOD; ?></legend>

            <p><?php echo $order->info['payment_method']; ?></p>

<?php
    if (tep_not_null($order->info['cc_type']) || tep_not_null($order->info['cc_owner']) || tep_not_null($order->info['cc_number'])) {
?>

            <table class="table">
              <tr>
                <td><?php echo ENTRY_CREDIT_CARD_TYPE; ?></td>
                <td><?php echo $order->info['cc_type']; ?></td>
              </tr>
              <tr>
                <td><?php echo ENTRY_CREDIT_CARD_OWNER; ?></td>
                <td><?php echo $order->info['cc_owner']; ?></td>
              </tr>
              <tr>
                <td><?php echo ENTRY_CREDIT_CARD_NUMBER; ?></td>
                <td><?php echo $order->info['cc_number']; ?></td>
              </tr>
              <tr>
                <td><?php echo ENTRY_CREDIT_CARD_EXPIRES; ?></td>
                <td><?php echo $order->info['cc_expires']; ?></td>
              </tr>
            </table>

<?php
    }
?>
          </fieldset>
        </td>
        <td width="33%" valign="top">
          <fieldset style="border: 0; height: 100%;">
            <legend style="font-weight: bold;"><?php echo ENTRY_STATUS; ?></legend>

            <p><?php echo $order->info['status'] . '<br />' . (empty($order->info['last_modified']) ? tep_datetime_short($order->info['date_purchased']) : tep_datetime_short($order->info['last_modified'])); ?></p>
          </fieldset>
        </td>
        <td width="33%" valign="top">
          <fieldset style="border: 0; height: 100%;">
            <legend style="font-weight: bold;"><?php echo ENTRY_TOTAL; ?></legend>

            <p><?php echo $order->info['total']; ?></p>
          </fieldset>
        </td>
      </tr>
    </table>
  </div>

    <div role="tabpanel" class="tab-pane" role="tabpanel" aria-labelledby="section_products_tab" id="section_products_content">
        <table class="table">
            <thead>
                <tr class="dataTableHeadingRow">
                    <th class="dataTableHeadingContent" colspan="2"><?php echo TABLE_HEADING_PRODUCTS; ?></th>
                    <th class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></th>
                    <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TAX; ?></th>
                    <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_PRICE_EXCLUDING_TAX; ?></th>
                    <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_PRICE_INCLUDING_TAX; ?></th>
                    <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TOTAL_EXCLUDING_TAX; ?></th>
                    <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TOTAL_INCLUDING_TAX; ?></th>
                </tr>
            </thead>
<?php
    for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
      echo '      <tr class="dataTableRow">' . "\n" .
           '        <td class="dataTableContent" valign="top" align="right">' . $order->products[$i]['qty'] . '&nbsp;x</td>' . "\n" .
           '        <td class="dataTableContent" valign="top">' . $order->products[$i]['name'];

      if (isset($order->products[$i]['attributes']) && (sizeof($order->products[$i]['attributes']) > 0)) {
        for ($j = 0, $k = sizeof($order->products[$i]['attributes']); $j < $k; $j++) {
          echo '<br /><nobr><small>&nbsp;<i> - ' . $order->products[$i]['attributes'][$j]['option'] . ': ' . $order->products[$i]['attributes'][$j]['value'];
          if ($order->products[$i]['attributes'][$j]['price'] != '0') echo ' (' . $order->products[$i]['attributes'][$j]['prefix'] . $currencies->format($order->products[$i]['attributes'][$j]['price'] * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . ')';
          echo '</i></small></nobr>';
        }
      }

      echo '        </td>' . "\n" .
           '        <td class="dataTableContent" valign="top">' . $order->products[$i]['model'] . '</td>' . "\n" .
           '        <td class="dataTableContent" align="right" valign="top">' . tep_display_tax_value($order->products[$i]['tax']) . '%</td>' . "\n" .
           '        <td class="dataTableContent" align="right" valign="top"><strong>' . $currencies->format($order->products[$i]['final_price'], true, $order->info['currency'], $order->info['currency_value']) . '</strong></td>' . "\n" .
           '        <td class="dataTableContent" align="right" valign="top"><strong>' . $currencies->format(tep_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax'], true), true, $order->info['currency'], $order->info['currency_value']) . '</strong></td>' . "\n" .
           '        <td class="dataTableContent" align="right" valign="top"><strong>' . $currencies->format($order->products[$i]['final_price'] * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</strong></td>' . "\n" .
           '        <td class="dataTableContent" align="right" valign="top"><strong>' . $currencies->format(tep_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax'], true) * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</strong></td>' . "\n";
      echo '      </tr>' . "\n";
    }
?>
            <tfoot>
<?php
    foreach ( $order->totals as $ot ) {
      echo '          <tr>' . "\n" .
           '            <td align="right" class="smallText">' . $ot['title'] . '</td>' . "\n" .
           '            <td align="right" class="smallText">' . $ot['text'] . '</td>' . "\n" .
           '          </tr>' . "\n";
    }
?>
            </tfoot>
        </table>
    </div>

  <div role="tabpanel" class="tab-pane" role="tabpanel" aria-labelledby="section_status_history_tab" id="section_status_history_content">
    <?php echo tep_draw_form('status', 'orders.php', tep_get_all_get_params(array('action')) . 'action=update_order'); ?>

    <table class="table">
      <tr>
        <td><?php echo ENTRY_STATUS; ?></td>
        <td><?php echo tep_draw_pull_down_menu('status', $orders_statuses, $order->info['orders_status']); ?></td>
      </tr>
      <tr>
        <td valign="top"><?php echo ENTRY_ADD_COMMENT; ?></td>
        <td><?php echo tep_draw_textarea_field('comments', 'soft', '60', '6', null, 'style="width: 100%;"'); ?></td>
      </tr>
      <tr>
        <td><?php echo ENTRY_NOTIFY_CUSTOMER; ?></td>
        <td><?php echo tep_draw_checkbox_field('notify', '', true); ?></td>
      </tr>
      <tr>
        <td><?php echo ENTRY_NOTIFY_COMMENTS; ?></td>
        <td><?php echo tep_draw_checkbox_field('notify_comments', '', true); ?></td>
      </tr>
      <tr>
        <td colspan="2" align="right"><?php echo tep_draw_button(IMAGE_UPDATE, 'disk', null, 'primary'); ?></td>
      </tr>
    </table>

    </form>



    <table class="table">
        <thead>
            <tr class="dataTableHeadingRow">
                <th class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_DATE_ADDED; ?></th>
                <th class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_STATUS; ?></th>
                <th class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_COMMENTS; ?></th>
                <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_CUSTOMER_NOTIFIED; ?></th>
            </tr>
        </thead>

<?php
    $orders_history_query = tep_db_query("select orders_status_id, date_added, customer_notified, comments from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . tep_db_input($oID) . "' order by date_added desc");
    if (tep_db_num_rows($orders_history_query)) {
      while ($orders_history = tep_db_fetch_array($orders_history_query)) {
        echo '      <tr class="dataTableRow">' . "\n" .
             '        <td class="dataTableContent" valign="top">' . tep_datetime_short($orders_history['date_added']) . '</td>' . "\n" .
             '        <td class="dataTableContent" valign="top">' . $orders_status_array[$orders_history['orders_status_id']] . '</td>' . "\n" .
             '        <td class="dataTableContent" valign="top">' . nl2br(tep_db_output($orders_history['comments'])) . '&nbsp;</td>' . "\n" .
             '        <td class="dataTableContent" valign="top" align="right">';

        if ($orders_history['customer_notified'] == '1') {
          echo tep_image('images/icons/tick.gif', ICON_TICK);
        } else {
          echo tep_image('images/icons/cross.gif', ICON_CROSS);
        }

        echo '        </td>' . "\n" .
             '      </tr>' . "\n";
      }
    } else {
        echo '      <tr class="dataTableRow">' . "\n" .
             '        <td class="dataTableContent" colspan="5">' . TEXT_NO_ORDER_HISTORY . '</td>' . "\n" .
             '      </tr>' . "\n";
    }
?>

    </table>
  </div>
</div>
<?php
    echo $OSCOM_Hooks->call('orders', 'orderTab');
?>
<script>
$('#orderTabs a').click(function (e) {
  e.preventDefault();
  $(this).tab('show');
});
</script>

<?php
  } else {
?>
<div class="page-header">
    <?php echo tep_draw_form('orders', 'orders.php', '', 'get'); ?>
        <div class="input-group col-md-2 float-right p-1">
            <div class="input-group-prepend">
                <span class="input-group-text" id="basic-addon1"><?php echo HEADING_TITLE_SEARCH; ?></span>
            </div>
        <?php echo tep_draw_input_field('oID', '', 'size="12"') . tep_draw_hidden_field('action', 'edit'); ?>
        
        </div>
        <?php echo tep_hide_session_id(); ?>
    </form>
    
    <?php echo tep_draw_form('status', 'orders.php', '', 'get'); ?>
        <div class="input-group col-md-3 float-right p-1">
            <div class="input-group-prepend">
                <span class="input-group-text"><?php echo HEADING_TITLE_STATUS; ?></span>
            </div>        
        <?php echo tep_draw_pull_down_menu('status', array_merge(array(array('id' => '', 'text' => TEXT_ALL_ORDERS)), $orders_statuses), '', 'onchange="this.form.submit();"'); ?>
        </div>
        
        <?php echo tep_hide_session_id(); ?>
        </form>    
    
    <h1><?php echo HEADING_TITLE; ?></h1>
</div>
<div class="row">
	<div class="col-md-8">	
		<table class="table table-bordered table-striped table-hover">
        <thead>
            <tr class="dataTableHeadingRow">
                    <th class="dataTableHeadingContent"><?php echo TABLE_HEADING_CUSTOMERS; ?> aaa</th>
                    <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ORDER_TOTAL; ?></th>
                    <th class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_DATE_PURCHASED; ?></th>
                    <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_STATUS; ?></th>
                    <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</th>
            </tr>
        </thead>
<?php
    if (isset($_GET['cID'])) {
      $cID = tep_db_prepare_input($_GET['cID']);
      $orders_query_raw = "select o.orders_id, o.customers_name, o.customers_id, o.payment_method, o.date_purchased, o.last_modified, o.currency, o.currency_value, s.orders_status_name, ot.text as order_total from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id), " . TABLE_ORDERS_STATUS . " s where o.customers_id = '" . (int)$cID . "' and o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and ot.class = 'ot_total' order by orders_id DESC";
    } elseif (isset($_GET['status']) && is_numeric($_GET['status']) && ($_GET['status'] > 0)) {
      $status = tep_db_prepare_input($_GET['status']);
      $orders_query_raw = "select o.orders_id, o.customers_name, o.payment_method, o.date_purchased, o.last_modified, o.currency, o.currency_value, s.orders_status_name, ot.text as order_total from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id), " . TABLE_ORDERS_STATUS . " s where o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and s.orders_status_id = '" . (int)$status . "' and ot.class = 'ot_total' order by o.orders_id DESC";
    } else {
      $orders_query_raw = "select o.orders_id, o.customers_name, o.payment_method, o.date_purchased, o.last_modified, o.currency, o.currency_value, s.orders_status_name, ot.text as order_total from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id), " . TABLE_ORDERS_STATUS . " s where o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and ot.class = 'ot_total' order by o.orders_id DESC";
    }
    $orders_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $orders_query_raw, $orders_query_numrows);
    $orders_query = tep_db_query($orders_query_raw);
    while ($orders = tep_db_fetch_array($orders_query)) {
    if ((!isset($_GET['oID']) || (isset($_GET['oID']) && ($_GET['oID'] == $orders['orders_id']))) && !isset($oInfo)) {
        $oInfo = new objectInfo($orders);
      }

      if (isset($oInfo) && is_object($oInfo) && ($orders['orders_id'] == $oInfo->orders_id)) {
        echo '<tr id="defaultSelected" class="table-primary" onclick="document.location.href=\'' . tep_href_link('orders.php', tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $oInfo->orders_id . '&action=edit') . '\'">' . "\n";
      } else {
        echo '<tr class="dataTableRow" onclick="document.location.href=\'' . tep_href_link('orders.php', tep_get_all_get_params(array('oID')) . 'oID=' . $orders['orders_id']) . '\'">' . "\n";
      }
?>
                <td class="dataTableContent"><?php echo '<a href="' . tep_href_link('orders.php', tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $orders['orders_id'] . '&action=edit') . '">' . tep_image('images/icons/preview.gif', ICON_PREVIEW) . '</a>&nbsp;' . $orders['customers_name']; ?></td>
                <td class="dataTableContent" align="right"><?php echo strip_tags($orders['order_total']); ?></td>
                <td class="dataTableContent" align="center"><?php echo tep_datetime_short($orders['date_purchased']); ?></td>
                <td class="dataTableContent" align="right"><?php echo $orders['orders_status_name']; ?></td>
                <td class="dataTableContent" align="right"><?php if (isset($oInfo) && is_object($oInfo) && ($orders['orders_id'] == $oInfo->orders_id)) { echo tep_image('images/icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link('orders.php', tep_get_all_get_params(array('oID')) . 'oID=' . $orders['orders_id']) . '">' . tep_image('images/icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
            </tr>
<?php
    }
?>

        </table>
        <nav>
            <ul class="pagination float-left"><?php echo $orders_split->display_count($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_ORDERS); ?></ul>
            <?php echo $orders_split->display_links($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page'], tep_get_all_get_params(array('page', 'oID', 'action'))); ?>
        </nav>
	</div>    
<?php
  $heading = array();
  $contents = array();

  switch ($action) {
    case 'delete':
      $heading[] = array('text' => '<strong>' . TEXT_INFO_HEADING_DELETE_ORDER . '</strong>');

      $contents = array('form' => tep_draw_form('orders', 'orders.php', tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $oInfo->orders_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_INFO_DELETE_INTRO . '<br /><br /><strong>' . $cInfo->customers_firstname . ' ' . $cInfo->customers_lastname . '</strong>');
      $contents[] = array('text' => '<br />' . tep_draw_checkbox_field('restock') . ' ' . TEXT_INFO_RESTOCK_PRODUCT_QUANTITY);
      $contents[] = array('align' => 'center', 'text' => '<br />' . tep_draw_button(IMAGE_DELETE, 'trash', null, 'primary') . tep_draw_button(IMAGE_CANCEL, 'close', tep_href_link('orders.php', tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $oInfo->orders_id)));
      break;
    default:
      if (isset($oInfo) && is_object($oInfo)) {
        $heading[] = array('text' => '<strong>[' . $oInfo->orders_id . ']&nbsp;&nbsp;' . tep_datetime_short($oInfo->date_purchased) . '</strong>');

        $contents[] = array('align' => 'center', 'text' => tep_draw_button(IMAGE_EDIT, 'document', tep_href_link('orders.php', tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $oInfo->orders_id . '&action=edit')) . tep_draw_button(IMAGE_DELETE, 'trash', tep_href_link('orders.php', tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $oInfo->orders_id . '&action=delete')));
        $contents[] = array('align' => 'center', 'text' => tep_draw_button(IMAGE_ORDERS_INVOICE, 'document', tep_href_link('invoice.php', 'oID=' . $oInfo->orders_id), null, array('newwindow' => true)) . tep_draw_button(IMAGE_ORDERS_PACKINGSLIP, 'document', tep_href_link('packingslip.php', 'oID=' . $oInfo->orders_id), null, array('newwindow' => true)));
        $contents[] = array('text' => '<br />' . TEXT_DATE_ORDER_CREATED . ' ' . tep_date_short($oInfo->date_purchased));
        if (tep_not_null($oInfo->last_modified)) $contents[] = array('text' => TEXT_DATE_ORDER_LAST_MODIFIED . ' ' . tep_date_short($oInfo->last_modified));
        $contents[] = array('text' => '<br />' . TEXT_INFO_PAYMENT_METHOD . ' '  . $oInfo->payment_method);
      }
      break;
  }

	if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
		echo '<div class="col-md-4" >' . "\n";

		$box = new box;
		echo $box->infoBox($heading, $contents);

		echo '</div>' . "\n";
	}
    
    echo '</div>';//row end

  }

  require('includes/template_bottom.php');
  require('includes/application_bottom.php');
?>
