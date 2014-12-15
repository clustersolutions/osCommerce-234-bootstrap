<?php
/*
  $Id: gv_mail.php,v 1.3.2.4 2003/05/12 22:54:01 wilt Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2002 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  if (tep_not_null($argv[1])) $HTTP_GET_VARS['action'] = 'send_email_to_user';
  if (tep_not_null($argv[1])) $HTTP_POST_VARS['customers_email_address'] = $argv[1];
  if (tep_not_null($argv[2])) $HTTP_POST_VARS['amount'] = $argv[2];

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  if ( ($HTTP_GET_VARS['action'] == 'send_email_to_user') && ($HTTP_POST_VARS['customers_email_address'] || $HTTP_POST_VARS['email_to']) && (!$HTTP_POST_VARS['back_x']) ) {
    switch ($HTTP_POST_VARS['customers_email_address']) {
      case '***':
        $mail_query = tep_db_query("select customers_id, customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS);
        $mail_sent_to = TEXT_ALL_CUSTOMERS;
        break;
      case '**D':
        $mail_query = tep_db_query("select customers_id, customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS . " where customers_newsletter = '1'");
        $mail_sent_to = TEXT_NEWSLETTER_CUSTOMERS;
        break;
      default:
        $customers_email_address = tep_db_prepare_input($HTTP_POST_VARS['customers_email_address']);

        $mail_query = tep_db_query("select customers_id, customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($customers_email_address) . "'");
        $mail_sent_to = $HTTP_POST_VARS['customers_email_address'];
        if ($HTTP_POST_VARS['email_to']) {
          $mail_sent_to = $HTTP_POST_VARS['email_to'];
        }
        break;
    }

    $from = tep_db_prepare_input($HTTP_POST_VARS['from']);
    $subject = tep_db_prepare_input($HTTP_POST_VARS['subject']);
    while ($mail = tep_db_fetch_array($mail_query)) {
      $id1 = create_coupon_code($mail['customers_email_address']);
      $message = $HTTP_POST_VARS['message'];
      $message .= "\n\n" . TEXT_GV_WORTH  . $currencies->format($HTTP_POST_VARS['amount']) . "\n\n";
      $message .= TEXT_TO_REDEEM;
      $message .= TEXT_WHICH_IS . $id1 . TEXT_IN_CASE . "\n\n";
      if (SEARCH_ENGINE_FRIENDLY_URLS == 'true') {
//        $message .= HTTP_SERVER  . DIR_WS_CATALOG . 'gv_redeem.php' . '/gv_no,'.$id1 . "\n\n";
        $message .= HTTP_SERVER  . DIR_WS_CATALOG . 'gv_redeem.php' . '/gv_no/'.$id1 . "\n\n";
      } else {
        $message .= HTTP_SERVER  . DIR_WS_CATALOG . 'gv_redeem.php' . '?gv_no='.$id1 . "\n\n";
      }
      $message .= TEXT_OR_VISIT . HTTP_SERVER  . DIR_WS_CATALOG . TEXT_ENTER_CODE;

      //Let's build a message object using the email class
      $mimemessage = new email(array('X-Mailer: osCommerce bulk mailer'));
      // add the message to the object
      $mimemessage->add_text($message);
      $mimemessage->build_message();
    
      //$mimemessage->send($mail['customers_firstname'] . ' ' . $mail['customers_lastname'], $mail['customers_email_address'], '', $from, $subject);

      // Now create the coupon main and email entry
      $insert_query = tep_db_query("insert into " . TABLE_COUPONS . " (coupon_code, coupon_type, coupon_amount, date_created) values ('" . $id1 . "', 'G', '" . $HTTP_POST_VARS['amount'] . "', now())");
      $insert_id = tep_db_insert_id();
      $insert_query = tep_db_query("insert into " . TABLE_COUPON_EMAIL_TRACK . " (coupon_id, customer_id_sent, sent_firstname, emailed_to, date_sent) values ('" . $insert_id ."', '0', 'Admin', '" . $mail['customers_email_address'] . "', now() )"); 

      //auto redeem ttl 012814
      $gv_query = tep_db_query("insert into  " . TABLE_COUPON_REDEEM_TRACK . " (coupon_id, customer_id, redeem_date, redeem_ip) values ('" . $insert_id . "', '" . $mail['customers_id'] . "', now(),'" . gethostbyname('racinneusa.com') . "')");
      $gv_update = tep_db_query("update " . TABLE_COUPONS . " set coupon_active = 'N' where coupon_id = '" . $insert_id . "'");
      tep_gv_account_update($mail['customers_id'], $insert_id);

      //update order status notify customer if $argv[3] is not null ttl 012814
      if (tep_not_null($argv[3])) {
          include(DIR_WS_LANGUAGES . $language . '/orders.php');
        if ($argv[4] == 'CASHREWARD') {
          $check_status_query = tep_db_query("select customers_name, customers_email_address, orders_status, date_purchased from " . TABLE_ORDERS . " where orders_id = '" . (int)$argv[3] . "'");
          $check_status = tep_db_fetch_array($check_status_query);
          $orders_status_array = array();
          $status = 6;
          $orders_status_query = tep_db_query("select orders_status_id, orders_status_name from " . TABLE_ORDERS_STATUS . " where language_id = '" . (int)$languages_id . "'");
          while ($orders_status = tep_db_fetch_array($orders_status_query)) {
            $orders_statuses[] = array('id' => $orders_status['orders_status_id'],
                                       'text' => $orders_status['orders_status_name']);
            $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
          }

          tep_db_query("update " . TABLE_ORDERS . " set orders_status = " . $status . ", last_modified = now() where orders_id = '" . (int)$argv[3] . "'");

          $customer_notified = '0';
          $comments = sprintf(EMAIL_CASH_BACK_REWARD, $HTTP_POST_VARS['amount']);
          $notify_comments = '';
          $notify_comments = "\n\n" . sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments);

          $email = STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $argv[3] . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $argv[3], 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n" . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status] . $notify_comments);

          //tep_mail($check_status['customers_name'], 'lai@clustersolutions.net', sprintf(EMAIL_TEXT_SUBJECT, $argv[3]) . ' - ' . $orders_status_array[$status], $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
          tep_mail($check_status['customers_name'], $check_status['customers_email_address'], sprintf(EMAIL_TEXT_SUBJECT, $argv[3]) . ' - ' . $orders_status_array[$status], $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

          $customer_notified = '1';

          $htauser = (tep_not_null($admin['username']) ? '[' . $admin['username'] . ']' : '[SYSTEM]');

          tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, internal_comments) values ('" . (int)$argv[3]. "', '" . tep_db_input($status) . "', now(), '" . tep_db_input($customer_notified) . "', '" . tep_db_input($comments)  . "', '" . tep_db_input($htauser)  . "')");

        } elseif ($argv[4] == 'REVIEW') {

          $products_name_info_query = tep_db_query("select products_name from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id = '" . (int)$argv[3] . "'");   
          $products_name_info  = tep_db_fetch_array($products_name_info_query);

          $email = sprintf(EMAIL_GREETING, mb_convert_case($mail['customers_firstname'], MB_CASE_TITLE, "UTF-8") . ' ' . mb_convert_case($mail['customers_lastname'], MB_CASE_TITLE, "UTF-8")). "\n\n" . sprintf(EMAIL_TEXT_REVIEW_THANK_YOU, trim($products_name_info['products_name']), $argv[2]);

          tep_mail($mail['customers_firstname'] . ' ' . $mail['customers_lastname'], $mail['customers_email_address'], EMAIL_TEXT_REVIEW_SUBJECT, $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
        }
      }
    }
    if ($HTTP_POST_VARS['email_to']) {
      $id1 = create_coupon_code($HTTP_POST_VARS['email_to']);
      $message = tep_db_prepare_input($HTTP_POST_VARS['message']);
      $message .= "\n\n" . TEXT_GV_WORTH  . $currencies->format($HTTP_POST_VARS['amount']) . "\n\n";
      $message .= TEXT_TO_REDEEM;
      $message .= TEXT_WHICH_IS . $id1 . TEXT_IN_CASE . "\n\n";
      $message .= HTTP_SERVER  . DIR_WS_CATALOG . 'gv_redeem.php' . '?gv_no='.$id1 . "\n\n";
      $message .= TEXT_OR_VISIT . HTTP_SERVER  . DIR_WS_CATALOG  . TEXT_ENTER_CODE;
     
      //Let's build a message object using the email class
      $mimemessage = new email(array('X-Mailer: osCommerce bulk mailer'));
      // add the message to the object
      $mimemessage->add_text($message);
      $mimemessage->build_message();
      //$mimemessage->send('Friend', $HTTP_POST_VARS['email_to'], '', $from, $subject);
      // Now create the coupon email entry
      $insert_query = tep_db_query("insert into " . TABLE_COUPONS . " (coupon_code, coupon_type, coupon_amount, date_created) values ('" . $id1 . "', 'G', '" . $HTTP_POST_VARS['amount'] . "', now())");
      $insert_id = tep_db_insert_id();
      $insert_query = tep_db_query("insert into " . TABLE_COUPON_EMAIL_TRACK . " (coupon_id, customer_id_sent, sent_firstname, emailed_to, date_sent) values ('" . $insert_id ."', '0', 'Admin', '" . $HTTP_POST_VARS['email_to'] . "', now() )"); 
    }
    tep_redirect(tep_href_link(FILENAME_GV_MAIL, 'mail_sent_to=' . urlencode($mail_sent_to)));
  }

  if ( ($HTTP_GET_VARS['action'] == 'preview') && (!$HTTP_POST_VARS['customers_email_address']) && (!$HTTP_POST_VARS['email_to']) ) {
    $messageStack->add(ERROR_NO_CUSTOMER_SELECTED, 'error');
  }

  if ( ($HTTP_GET_VARS['action'] == 'preview') && (!$HTTP_POST_VARS['amount']) ) {
    $messageStack->add(ERROR_NO_AMOUNT_SELECTED, 'error');
  }

  if ($HTTP_GET_VARS['mail_sent_to']) {
    $messageStack->add(sprintf(NOTICE_EMAIL_SENT_TO, $HTTP_GET_VARS['mail_sent_to']), 'notice');
  }
  require(DIR_WS_INCLUDES . 'template_top.php');
?>
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
<!-- body_text //-->
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
  if ( ($HTTP_GET_VARS['action'] == 'preview') && ($HTTP_POST_VARS['customers_email_address'] || $HTTP_POST_VARS['email_to']) ) {
    switch ($HTTP_POST_VARS['customers_email_address']) {
      case '***':
        $mail_sent_to = TEXT_ALL_CUSTOMERS;
        break;
      case '**D':
        $mail_sent_to = TEXT_NEWSLETTER_CUSTOMERS;
        break;
      default:
        $mail_sent_to = $HTTP_POST_VARS['customers_email_address'];
        if ($HTTP_POST_VARS['email_to']) {
          $mail_sent_to = $HTTP_POST_VARS['email_to'];
        }
        break;
    }
?>
          <tr><?php echo tep_draw_form('mail', FILENAME_GV_MAIL, 'action=send_email_to_user'); ?>
            <td><table border="0" width="100%" cellpadding="0" cellspacing="2">
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="smallText"><b><?php echo TEXT_CUSTOMER; ?></b><br><?php echo $mail_sent_to; ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="smallText"><b><?php echo TEXT_FROM; ?></b><br><?php echo htmlspecialchars(stripslashes($HTTP_POST_VARS['from'])); ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="smallText"><b><?php echo TEXT_SUBJECT; ?></b><br><?php echo htmlspecialchars(stripslashes($HTTP_POST_VARS['subject'])); ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="smallText"><b><?php echo TEXT_AMOUNT; ?></b><br><?php echo nl2br(htmlspecialchars(stripslashes($HTTP_POST_VARS['amount']))); ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="smallText"><b><?php echo TEXT_MESSAGE; ?></b><br><?php echo nl2br(htmlspecialchars(stripslashes($HTTP_POST_VARS['message']))); ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td>
<?php
/* Re-Post all POST'ed variables */
    reset($HTTP_POST_VARS);
    while (list($key, $value) = each($HTTP_POST_VARS)) {
      if (!is_array($HTTP_POST_VARS[$key])) {
        echo tep_draw_hidden_field($key, htmlspecialchars(stripslashes($value)));
      }
    }
?>
                <table border="0" width="100%" cellpadding="0" cellspacing="2">
                  <tr>
                    <td><?php echo tep_image_submit('button_back.gif', IMAGE_BACK, 'name="back"'); ?></td>
                    <td align="right"><?php echo  tep_draw_button(IMAGE_CANCEL, 'cancel',tep_href_link(FILENAME_GV_MAIL)) .  tep_draw_button(IMAGE_SEND_EMAIL, 'mail-open',null,'primary'); ?></td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
          </form></tr>
<?php
  } else {
?>
          <tr><?php echo tep_draw_form('mail', FILENAME_GV_MAIL, 'action=preview'); ?>
            <td><table border="0" cellpadding="0" cellspacing="2">
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
<?php
    $customers = array();
    $customers[] = array('id' => '', 'text' => TEXT_SELECT_CUSTOMER);
    $customers[] = array('id' => '***', 'text' => TEXT_ALL_CUSTOMERS);
    $customers[] = array('id' => '**D', 'text' => TEXT_NEWSLETTER_CUSTOMERS);
    $mail_query = tep_db_query("select customers_email_address, customers_firstname, customers_lastname from " . TABLE_CUSTOMERS . " order by customers_lastname");
    while($customers_values = tep_db_fetch_array($mail_query)) {
      $customers[] = array('id' => $customers_values['customers_email_address'],
                           'text' => $customers_values['customers_lastname'] . ', ' . $customers_values['customers_firstname'] . ' (' . $customers_values['customers_email_address'] . ')');
    }
?>
              <tr>
                <td class="main"><?php echo TEXT_CUSTOMER; ?></td>
                <td><?php echo tep_draw_pull_down_menu('customers_email_address', $customers, $HTTP_GET_VARS['customer']);?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
               <tr>
                <td class="main"><?php echo TEXT_TO; ?></td>
                <td><?php echo tep_draw_input_field('email_to'); ?><?php echo '&nbsp;&nbsp;' . TEXT_SINGLE_EMAIL; ?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
             <tr>
                <td class="main"><?php echo TEXT_FROM; ?></td>
                <td><?php echo tep_draw_input_field('from', EMAIL_FROM); ?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo TEXT_SUBJECT; ?></td>
                <td><?php echo tep_draw_input_field('subject'); ?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td valign="top" class="main"><?php echo TEXT_AMOUNT; ?></td>
                <td><?php echo tep_draw_input_field('amount'); ?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td valign="top" class="main"><?php echo TEXT_MESSAGE; ?></td>
                <td><?php echo tep_draw_textarea_field('message', 'soft', '60', '15'); ?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td colspan="2" align="right"><?php echo tep_draw_button(IMAGE_SEND_EMAIL, 'mail-open',null,'primary'); ?></td>
              </tr>
            </table></td>
          </form></tr>
<?php
  }
?>
<!-- body_text_eof //-->
        </table></td>
      </tr>
    </table></td>
  </tr>
</table>

<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
