<?php
/*
  $Id: gv_redeem.php,v 1.3.2.1 2003/04/18 15:52:40 wilt Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2002 - 2003 osCommerce

  Gift Voucher System v1.0
  Copyright (c) 2001, 2002 Ian C Wilson
  http://www.phesis.org

  Released under the GNU General Public License
*/

  require('includes/application_top.php');
if (!tep_session_is_registered('customer_id')) {
$navigation->set_snapshot();
tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
}

	if (isset($HTTP_GET_VARS['gv_no'])) {
	$error = true;
	$voucher_number=tep_db_prepare_input($HTTP_GET_VARS['gv_no']);
	$gv_query = tep_db_query("select c.coupon_id, c.coupon_amount from " . TABLE_COUPONS . " c, " . TABLE_COUPON_EMAIL_TRACK . " et where coupon_code = '" . addslashes($voucher_number) . "' and c.coupon_id = et.coupon_id");
	if (tep_db_num_rows($gv_query) >0) {
      $coupon = tep_db_fetch_array($gv_query);

      $redeem_query = tep_db_query("select coupon_id from ". TABLE_COUPON_REDEEM_TRACK . " where coupon_id = '" . $coupon['coupon_id'] . "'");

      if (tep_db_num_rows($redeem_query) == 0 ) {
        if (!tep_session_is_registered('gv_id')) {
          tep_session_register('gv_id');
        }
        $gv_id = $coupon['coupon_id'];
        $error = false;
      } else {
        $error = true;
      }
    }
  } else {
    tep_redirect(FILENAME_DEFAULT);
  }

  if ((!$error) && (tep_session_is_registered('customer_id'))) {
// Update redeem status
    $gv_query = tep_db_query("insert into  " . TABLE_COUPON_REDEEM_TRACK . " (coupon_id, customer_id, redeem_date, redeem_ip) values ('" . $coupon['coupon_id'] . "', '" . $customer_id . "', now(),'" . $_SERVER['REMOTE_ADDR'] . "')");
    $gv_update = tep_db_query("update " . TABLE_COUPONS . " set coupon_active = 'N' where coupon_id = '" . $coupon['coupon_id'] . "'");
    tep_gv_account_update($customer_id, $gv_id);
    tep_session_unregister('gv_id');   
  } 
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_GV_REDEEM);

  $breadcrumb->add(NAVBAR_TITLE); 
  require(DIR_WS_INCLUDES . 'template_top.php');

?>
<h1><?php echo HEADING_TITLE; ?></h1>
<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td width="100%" valign="top">
    <table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td>
	<table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><?php echo TEXT_INFORMATION; ?></td>
          </tr>
<?php
  $message = sprintf(TEXT_VALID_GV, $currencies->format($coupon['coupon_amount']));
  if ($error) {
    $message = TEXT_INVALID_GV;
  }
?>
          <tr>
            <td class="main"><?php echo $message; ?></td>
          </tr>
          <tr>
            <td align="right"><span class="buttonAction"><?php echo tep_draw_button(IMAGE_BUTTON_CONTINUE, 'triangle-1-e', tep_href_link(FILENAME_DEFAULT)); ?></span></td>
          </tr>
        </table>
	</td>
      </tr>
    </table>
    </td>
  </tr>
</table>
<?php

  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
