<?php
/*
  $Id: gv_send.php,v 1.1.2.3 2003/05/12 22:57:20 wilt Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2002 - 2003 osCommerce

  Gift Voucher System v1.0
  Copyright (c) 2001, 2002 Ian C Wilson
  http://www.phesis.org

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  require('includes/classes/http_client.php');
  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot();
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_GV_SEND);

  if (($HTTP_POST_VARS['back_x']) || ($HTTP_POST_VARS['back_y'])) {
    $HTTP_GET_VARS['action'] = '';
  }
  if ($HTTP_GET_VARS['action'] == 'send') {
    $error = false;
    if (!tep_validate_email(trim($HTTP_POST_VARS['email']))) {
      $error = true;
      $error_email = ERROR_ENTRY_EMAIL_ADDRESS_CHECK;
    }
    $gv_query = tep_db_query("select amount from " . TABLE_COUPON_GV_CUSTOMER . " where customer_id = '" . $customer_id . "'");
    $gv_result = tep_db_fetch_array($gv_query);
    $customer_amount = $gv_result['amount'];
    $gv_amount = trim($HTTP_POST_VARS['amount']);
    if (ereg('[^0-9/.]', $gv_amount)) {
      $error = true;
      $error_amount = ERROR_ENTRY_AMOUNT_CHECK; 
    }
    if ($gv_amount>$customer_amount || $gv_amount == 0) {
      $error = true; 
      $error_amount = ERROR_ENTRY_AMOUNT_CHECK; 
    } 
  }
  if ($HTTP_GET_VARS['action'] == 'process') {
    $id1 = create_coupon_code($mail['customers_email_address']);
    $gv_query = tep_db_query("select amount from " . TABLE_COUPON_GV_CUSTOMER . " where customer_id='".$customer_id."'");
    $gv_result=tep_db_fetch_array($gv_query);
    $new_amount=$gv_result['amount']-$HTTP_POST_VARS['amount'];
    $gv_amount_redeemed = $gv_result['amount_redeemed'] + $HTTP_POST_VARS['amount'];
    if ($new_amount<0) {
      $error= true;
      $error_amount = ERROR_ENTRY_AMOUNT_CHECK; 
      $HTTP_GET_VARS['action'] = 'send';
    } else {
      $gv_query=tep_db_query("update " . TABLE_COUPON_GV_CUSTOMER . " set amount = '" . $new_amount . "',amount_redeemed = '" . $gv_amount_redeemed . "' where customer_id = '" . $customer_id . "'");
      $gv_query=tep_db_query("select customers_firstname, customers_lastname from " . TABLE_CUSTOMERS . " where customers_id = '" . $customer_id . "'");
      $gv_customer=tep_db_fetch_array($gv_query);
      $gv_query=tep_db_query("insert into " . TABLE_COUPONS . " (coupon_type, coupon_code, date_created, coupon_amount) values ('G', '" . $id1 . "', NOW(), '" . $HTTP_POST_VARS['amount'] . "')");
      $gv_query=tep_db_query("select coupon_id from " . TABLE_COUPONS . " where coupon_code = '" . $id1 . "'");
      $gv_new_coupon=tep_db_fetch_array($gv_query);
      $gv_query=tep_db_query("insert into " . TABLE_COUPON_EMAIL_TRACK . " (coupon_id, customer_id_sent, sent_firstname, sent_lastname, emailed_to, date_sent) values ('" . $gv_new_coupon['coupon_id'] . "' ,'" . $customer_id . "', '" . addslashes($gv_customer['customers_firstname']) . "', '" . addslashes($gv_customer['customers_lastname']) . "', '" . $HTTP_POST_VARS['email'] . "', now())");
//      $insert_id = tep_db_insert_id($gv_query);
//      $gv_query=tep_db_query("insert into " . TABLE_COUPON_EMAIL_TRACK . " (coupon_id, customer_id_sent, sent_firstname, sent_lastname, emailed_to, date_sent) values ('" . $insert_id . "' ,'" . $customer_id . "', '" . addslashes($gv_customer['customers_firstname']) . "', '" . addslashes($gv_customer['customers_lastname']) . "', '" . $HTTP_POST_VARS['email'] . "', now())");

      $gv_email = STORE_NAME . "\n" .
              EMAIL_SEPARATOR . "\n" .
              sprintf(EMAIL_GV_TEXT_HEADER, $currencies->format($HTTP_POST_VARS['amount'])) . "\n" .
              EMAIL_SEPARATOR . "\n" . 
              sprintf(EMAIL_GV_FROM, stripslashes($HTTP_POST_VARS['send_name'])) . "\n";
      if (isset($HTTP_POST_VARS['message'])) {
        $gv_email .= EMAIL_GV_MESSAGE . "\n";
        if (isset($HTTP_POST_VARS['to_name'])) {
          $gv_email .= sprintf(EMAIL_GV_SEND_TO, stripslashes($HTTP_POST_VARS['to_name'])) . "\n\n";
        }
        $gv_email .= stripslashes($HTTP_POST_VARS['message']) . "\n\n";
      } 
      $gv_email .= sprintf(EMAIL_GV_REDEEM, $id1) . "\n\n";
      $gv_email .= EMAIL_GV_LINK . ' ' . "<a HREF='" . 
// ################# Added CGV
      tep_href_link(FILENAME_GV_REDEEM, 'gv_no=' . $id1,'NONSSL',false) . "'>" . tep_href_link(FILENAME_GV_REDEEM, 'gv_no=' .       $id1,'NONSSL',false) . "</a>\n" ;
// ################# End Added CGV
      $gv_email .= "\n\n";  
      $gv_email .= EMAIL_GV_FIXED_FOOTER . "\n\n";
      $gv_email .= EMAIL_GV_SHOP_FOOTER . "\n\n";;
      $gv_email_subject = sprintf(EMAIL_GV_TEXT_SUBJECT, stripslashes($HTTP_POST_VARS['send_name']));             
      tep_mail('', $HTTP_POST_VARS['email'], $gv_email_subject, nl2br($gv_email), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, '');
    }
  }
  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(GV_SEND));
  require(DIR_WS_INCLUDES . 'template_top.php');
?>
<h1><?php echo HEADING_TITLE; ?></h1>
<table border="0" width="100%" cellspacing="3" cellpadding="3">
<?php
  if ($HTTP_GET_VARS['action'] == 'process') {
?>
	<tr>
		<td class="main"><?php echo TEXT_SUCCESS; ?></td>
	</tr>
		<td><div class="buttonSet"><span class="buttonAction"><?php echo tep_draw_button(IMAGE_BUTTON_CONTINUE, 'triangle-1-e', tep_href_link(FILENAME_DEFAULT)); ?></span></div></td>
  	</tr>
<?php
  }  
  if ($HTTP_GET_VARS['action'] == 'send' && !$error) {
    // validate entries
      $gv_amount = (double) $gv_amount;
      $gv_query = tep_db_query("select customers_firstname, customers_lastname from " . TABLE_CUSTOMERS . " where customers_id = '" . $customer_id . "'");
      $gv_result = tep_db_fetch_array($gv_query);
      $send_name = $gv_result['customers_firstname'] . ' ' . $gv_result['customers_lastname'];
?>
	<tr>
		<td>
			<form action="<?php echo tep_href_link(FILENAME_GV_SEND, 'action=process', 'NONSSL'); ?>" method="post">
			<table border="0" width="100%" cellspacing="0" cellpadding="2">
				<tr>
					<td class="main"><?php echo sprintf(MAIN_MESSAGE, $currencies->format($HTTP_POST_VARS['amount']), stripslashes($HTTP_POST_VARS['to_name']), $HTTP_POST_VARS['email'], stripslashes($HTTP_POST_VARS['to_name']), $currencies->format($HTTP_POST_VARS['amount']), $send_name); ?></td>
				</tr>
<?php
      if ($HTTP_POST_VARS['message']) {
?>
				<tr>
					<td class="main"><?php echo sprintf(PERSONAL_MESSAGE, $gv_result['customers_firstname']); ?></td>
				</tr>
				<tr>
					<td class="main"><?php echo stripslashes($HTTP_POST_VARS['message']); ?></td>
				</tr>
<?php
      }

      echo tep_draw_hidden_field('send_name', $send_name) . tep_draw_hidden_field('to_name', stripslashes($HTTP_POST_VARS['to_name'])) . tep_draw_hidden_field('email', $HTTP_POST_VARS['email']) . tep_draw_hidden_field('amount', $gv_amount) . tep_draw_hidden_field('message', stripslashes($HTTP_POST_VARS['message']));
?>
				<tr>
<?php
    $back = sizeof($navigation->path)-1;
?>
					<td><?php echo '<a href="' . tep_href_link($navigation->path[$back]['page'], tep_array_to_string($navigation->path[$back]['get'], array('action')), $navigation->path[$back]['mode']) . '">' . tep_draw_button(IMAGE_BUTTON_BACK, 'triangle-1-w', $back_link) . '</a>'; ?></td>
					<td align="right"><span class="buttonAction"><?php echo tep_draw_hidden_field('action', 'send') . tep_draw_button(IMAGE_BUTTON_CONTINUE, 'triangle-1-e', null, 'primary'); ?></span></td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
<?php
  } elseif ($HTTP_GET_VARS['action']=='' || $error) {
?>
	<tr>
		<td class="main"><?php echo HEADING_TEXT; ?></td>
	</tr>
	<tr>
		<td>
			<form action="<?php echo tep_href_link(FILENAME_GV_SEND, 'action=send', 'NONSSL'); ?>" method="post">
			<table border="0" width="100%" cellspacing="0" cellpadding="2">
				<tr>
					<td class="main"><?php echo ENTRY_NAME; ?><br><?php echo tep_draw_input_field('to_name', stripslashes($HTTP_POST_VARS['to_name']));?></td>
				</tr>
				<tr>
					<td class="main"><?php echo ENTRY_EMAIL; ?><br><?php echo tep_draw_input_field('email', $HTTP_POST_VARS['email']); if ($error) echo $error_email; ?></td>
				</tr>
				<tr>
					<td class="main"><?php echo ENTRY_AMOUNT; ?><br><?php echo tep_draw_input_field('amount', $HTTP_POST_VARS['amount'], '', '', false); if ($error) echo $error_amount; ?></td>
				</tr>
				<tr>
					<td class="main"><?php echo ENTRY_MESSAGE; ?><br><?php echo tep_draw_textarea_field('message', 'soft', 50, 15, stripslashes($HTTP_POST_VARS['message'])); ?></td>
				</tr>
			</table>
			<table border="0" width="100%" cellspacing="0" cellpadding="2">
				<tr>
<?php
   $back = sizeof($navigation->path)-2;
?>
					<td><?php echo '<a href="' . tep_href_link($navigation->path[$back]['page'], tep_array_to_string($navigation->path[$back]['get'], array('action')), $navigation->path[$back]['mode']) . '">' . tep_draw_button(IMAGE_BUTTON_BACK, 'triangle-1-w', $back_link) . '</a>'; ?></td>
					<td align="right"><span class="buttonAction"><?php echo tep_draw_hidden_field('action', 'send') . tep_draw_button(IMAGE_BUTTON_CONTINUE, 'triangle-1-e', null, 'primary'); ?></span></td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
<?php
  }
?>
</table>
<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
