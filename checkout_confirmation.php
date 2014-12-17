<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  $HTTP_POST_VARS['payment'] = $HTTP_GET_VARS['payment'];

// if the customer is not logged on, redirect them to the login page
  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_SHIPPING));
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }

// if there is nothing in the customers cart, redirect them to the shopping cart page
  if ($cart->count_contents() < 1) {
    tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
  }

// avoid hack attempts during the checkout procedure by checking the internal cartID
  if (isset($cart->cartID) && tep_session_is_registered('cartID')) {
    if ($cart->cartID != $cartID) {
      tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
    }
  }

// if no shipping method has been selected, redirect the customer to the shipping method selection page
  if (!tep_session_is_registered('shipping')) {
    tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
  }

  if (!tep_session_is_registered('payment')) tep_session_register('payment');
  if (isset($HTTP_POST_VARS['payment'])) $payment = $HTTP_POST_VARS['payment'];

  if (!tep_session_is_registered('comments')) tep_session_register('comments');
  if (isset($HTTP_POST_VARS['comments']) && tep_not_null($HTTP_POST_VARS['comments'])) {
    $comments = tep_db_prepare_input($HTTP_POST_VARS['comments']);
  }

// load the selected shipping module
  require(DIR_WS_CLASSES . 'shipping.php');
  $shipping_modules = new shipping($shipping);

  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;

  require(DIR_WS_CLASSES . 'order_total.php');
  $order_total_modules = new order_total;
  $order_total_modules->collect_posts();
  if (!$HTTP_POST_VARS['cot_gv']) {
    if (tep_session_is_registered('cot_gv')) tep_session_unregister('cot_gv');
  }
  //$order_total_modules->pre_confirmation_check(); useless crap written by ametures!
  $otm = $order_total_modules->process();
  foreach ($otm as $votm) {
    if ($votm['code'] == 'ot_total') {
      if ($votm['value'] == 0) {
        if (!tep_session_is_registered('credit_covers')) tep_session_register('credit_covers');
        $credit_covers = true;
      } else {
        if (tep_session_is_registered('credit_covers')) tep_session_unregister('credit_covers');
        $credit_covers = false;
      }
    }
    if ($votm['code'] == 'ot_gv') $aa_gv = $votm['value'];
    if ($votm['code'] == 'ot_subtotal') $aa_subtotal = $votm['value'];
    if ($votm['code'] == 'ot_coupon') $aa_coupon = $votm['value'];
    //echo $votm['code'] . ' ' . $GLOBALS[$votm['code']]->output[0]['value'] . '<br>';
  }

// added for chicken and egg problem with shipping based on net promo and not subtotal ttl 063014
  if (isset($shipping['flatrate'])) {
    if (($aa_subtotal - $aa_gv - $aa_coupon <= $shipping['minpurchase']) && $shipping['cost'] == $shipping['flatrate']) {
      $shipping['cost'] = $shipping['nonflatrate'];
      if (!tep_session_is_registered('tmp_cot_gv')) tep_session_register('tmp_cot_gv');
      $tmp_cot_gv = $HTTP_POST_VARS['cot_gv'];
      if (!tep_session_is_registered('tmp_gv_redeem_code')) tep_session_register('tmp_gv_redeem_code');
      $tmp_gv_redeem_code = $HTTP_POST_VARS['gv_redeem_code'];
      tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'));
    }
    if (($aa_subtotal - $aa_gv - $aa_coupon > $shipping['minpurchase']) && $shipping['cost'] == $shipping['nonflatrate']) {
      $shipping['cost'] = $shipping['flatrate'];
      if (!tep_session_is_registered('tmp_cot_gv')) tep_session_register('tmp_cot_gv');
      $tmp_cot_gv = $HTTP_POST_VARS['cot_gv'];
      if (!tep_session_is_registered('tmp_gv_redeem_code')) tep_session_register('tmp_gv_redeem_code');
      $tmp_gv_redeem_code = $HTTP_POST_VARS['gv_redeem_code'];
      tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'));
    }
  }

// load the selected payment module
  require(DIR_WS_CLASSES . 'payment.php');
  $payment_modules = new payment($payment);

  $payment_modules->update_status();

//  if ( ($payment_modules->selected_module != $payment) || ( is_array($payment_modules->modules) && (sizeof($payment_modules->modules) > 1) && !is_object($$payment) ) || (is_object($$payment) && ($$payment->enabled == false)) ) {
/* CCGV - BEGIN */
  if ( ( is_array($payment_modules->modules) && (sizeof($payment_modules->modules) > 1) && (!is_object($$payment)) && (!$credit_covers) ) || (is_object($$payment) && ($$payment->enabled == false)) ) {

  // from original ccgv ttl ...if (((is_array($payment_modules->modules)) && (sizeof($payment_modules->modules) > 1) && (!is_object($$payment)) && (!$credit_covers)) || ((is_object($$payment)) && ($$payment->enabled == false))) {
//  CCGV - END
    tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, 'error_message=' . urlencode(ERROR_NO_PAYMENT_MODULE_SELECTED), 'SSL'));
  }

  if (is_array($payment_modules->modules)) {
    $payment_modules->pre_confirmation_check();
  }

// Stock Check
  $any_out_of_stock = false;
  if (STOCK_CHECK == 'true') {
    for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
      if (tep_check_stock($order->products[$i]['id'], $order->products[$i]['qty'])) {
        $any_out_of_stock = true;
      }
    }
    // Out of Stock
    if ( (STOCK_ALLOW_CHECKOUT != 'true') && ($any_out_of_stock == true) ) {
      tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
    }
  }

  if (!tep_session_is_registered('comments')) tep_session_register('comments');
  if (isset($HTTP_POST_VARS['comments']) && tep_not_null($HTTP_POST_VARS['comments'])) {
    $comments = tep_db_prepare_input($HTTP_POST_VARS['comments']);
  }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_CONFIRMATION);

  $breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
  $breadcrumb->add(NAVBAR_TITLE_2);

  require(DIR_WS_INCLUDES . 'template_top.php');
?>

<div class="page-header">
  <h1><?php echo HEADING_TITLE; ?></h1>
</div>

<?php

  if (isset($HTTP_GET_VARS['payment_error']) && is_object(${$HTTP_GET_VARS['payment_error']}) && ($error = ${$HTTP_GET_VARS['payment_error']}->get_error())) {
    $messageStack->add('payment_error', '<strong>' . tep_output_string_protected($error['title']) . ':</strong> ' . tep_output_string_protected($error['error']));
  }

  if ($messageStack->size('payment_error') > 0) {
    echo $messageStack->output('payment_error');
  }

  if ($messageStack->size('checkout_confirmation') > 0) {
    echo $messageStack->output('checkout_confirmation');
  }

?>

<div class="contentContainer">
  <div class="contentText">

  <div class="row">
    <?php
    if ($sendto != false) {
      ?>
      <div class="col-sm-6">
        <div class="panel panel-info  equal-height">
          <div class="panel-heading"><?php echo '<strong>' . HEADING_DELIVERY_ADDRESS . '</strong>' . tep_draw_button(TEXT_EDIT, 'glyphicon glyphicon-edit', tep_href_link(FILENAME_CHECKOUT_SHIPPING_ADDRESS, '', 'SSL'), NULL, NULL, 'pull-right btn-info btn-xs' ); ?></div>
          <div class="panel-body">
            <?php echo tep_address_format($order->delivery['format_id'], $order->delivery, 1, ' ', '<br />'); ?>
          </div>
        </div>
      </div>
      <?php
    }
    ?>
    <div class="col-sm-6">
      <div class="panel panel-warning  equal-height">
        <div class="panel-heading"><?php echo '<strong>' . HEADING_BILLING_ADDRESS . '</strong>' . tep_draw_button(TEXT_EDIT, 'glyphicon glyphicon-edit', tep_href_link(FILENAME_CHECKOUT_PAYMENT_ADDRESS, '', 'SSL'), NULL, NULL, 'pull-right btn-info btn-xs' ); ?></div>
        <div class="panel-body">
          <?php echo tep_address_format($order->billing['format_id'], $order->billing, 1, ' ', '<br />'); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-12">
      <div class="panel panel-default">
        <div class="panel-heading"><?php echo '<strong>' . HEADING_PRODUCTS . '</strong>' . tep_draw_button(TEXT_EDIT, 'glyphicon glyphicon-edit', tep_href_link(FILENAME_SHOPPING_CART), NULL, NULL, 'pull-right btn-info btn-xs' ); ?></div>
        <div class="panel-body">
          <table width="100%" class="table-hover order_confirmation">
            <tbody>

<?php
  for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
    echo '              <tr>' . "\n" .
         '                <td align="right" valign="top" width="30">' . $order->products[$i]['qty'] . '&nbsp;x&nbsp;</td>' . "\n" .
         '                <td valign="top">' . $order->products[$i]['name'];

    if (STOCK_CHECK == 'true') {
      echo tep_check_stock($order->products[$i]['id'], $order->products[$i]['qty']);
    }

    if ( (isset($order->products[$i]['attributes'])) && (sizeof($order->products[$i]['attributes']) > 0) ) {
      for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
        echo '<br /><nobr><small>&nbsp;<i> - ' . $order->products[$i]['attributes'][$j]['option'] . ': ' . $order->products[$i]['attributes'][$j]['value'] . '</i></small></nobr>';
      }
    }

    echo '</td>' . "\n";

    if (sizeof($order->info['tax_groups']) > 1) echo '                <td valign="top" align="right">' . tep_display_tax_value($order->products[$i]['tax']) . '%</td>' . "\n";

    echo '                <td align="right" valign="top">' . ($order->products[$i]['orig_price'] > $order->products[$i]['final_price'] ? '<del>' . $currencies->display_price($order->products[$i]['orig_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . '</del>&nbsp;&nbsp;<span class="productSpecialPrice">' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . '</span>' : $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty'])) . '</td>' . "\n" .
         '              </tr>' . "\n";
  }
?>


            </tbody>
          </table>
          <hr>
          <table width="100%" class="pull-right">

<?php
  if (MODULE_ORDER_TOTAL_INSTALLED) {
    echo $order_total_modules->output();
  }
?>

          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
<?php
/* CCGV - BEGIN */
  $have_promos = false;
  $promo_code = $order_total_modules->credit_selection();
  $store_credit = $order_total_modules->sub_credit_selection();
  if (tep_not_null($promo_code) || tep_not_null($store_credit)) {
    $have_promos = true;
  $gv_query = tep_db_query("select amount from " . TABLE_COUPON_GV_CUSTOMER . " where customer_id = '" . $customer_id . "'");
  $gv_result = tep_db_fetch_array($gv_query);
?>
    <div class="col-sm-6">
      <div class="panel panel-success">
        <div class="panel-heading"><?php echo '<strong>' . TABLE_HEADING_PROMO_CODE . '</strong>'; ?></div>
        <div class="panel-body">
        <?php
          echo tep_draw_form('checkout_payment_gift', tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'), 'post');
          echo '<div style="margin-bottom: 15px;">' . $promo_code . '</div>';
          if (tep_session_is_registered('customer_id')){
            if ($gv_result['amount'] > 0){
              echo '<div style="margin-bottom: 5px;">' . $store_credit . '&nbsp;&nbsp;' . sprintf(VOUCHER_BALANCE, $currencies->format($gv_result['amount'])) . '</div>';
            }
          }

          echo '<div class="pull-right">' . tep_draw_button(IMAGE_BUTTON_APPLY, 'glyphicon glyphicon-chevron-right', null, 'primary') . '</div>';
          /*
          if (isset($HTTP_GET_VARS['payment_error']) && is_object(${$HTTP_GET_VARS['payment_error']}) && ($error = ${$HTTP_GET_VARS['payment_error']}->get_error())) {
        ?>
          <p class="messageStackError"><?php echo tep_output_string_protected($error['error']); ?></p>
        <?php
          }
          */
        ?>
        </form>
        </div>
      </div>
    </div>
<?php
  }
  if (isset($$payment->form_action_url)) {
    $form_action_url = $$payment->form_action_url;
  } else {
    $form_action_url = tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');
  }

  echo tep_draw_form('checkout_confirmation', $form_action_url, 'post');

  if (is_array($payment_modules->modules)) {
    if ($confirmation = $payment_modules->confirmation()) {
?>
    <div id="payment" class="col-sm-6 <?php echo ($have_promos ? '' : 'col-sm-push-6'); ?>">
      <div class="panel panel-success">
        <div class="panel-heading"><?php echo '<strong>' . $confirmation['image'] . '</strong>'; ?></div>
        <div class="panel-body">
          <div class="contentText row">
<?php
      if (tep_not_null($confirmation['title'])) {
        echo '            <div class="col-sm-12">';
        echo '              <div class="alert alert-danger">';
        echo $confirmation['title'];
        echo '              </div>';
        echo '            </div>';
      }
?>
<?php
      if (isset($confirmation['fields'])) {
        echo '            <div class="col-sm-12">';
        for ($i=0, $n=sizeof($confirmation['fields']); $i<$n; $i++) {
          echo $confirmation['fields'][$i]['title'] . ' ' . $confirmation['fields'][$i]['field'];
        }
        echo '            </div>';
      }
?>
          </div>
        </div>
      </div>
    </div>
    <script>
      $(document).ready(function(){
        if (1 == <?php echo ($credit_covers == true ? 1 : 0); ?>) {
          $("#payment select").attr("disabled", "disabled");
          $("#payment input[type='text']").attr("disabled", "disabled");
          $("#payment").fadeTo('slow', 0.33);
        }
      });
    </script>
<?php
    }
  }
?>
    <div class="col-sm-6 <?php echo ($have_promos ? 'col-sm-push-6' : 'col-sm-pull-6'); ?>">
      <div class="panel panel-info">
        <div class="panel-heading"><?php echo '<strong>' . TABLE_HEADING_COMMENTS . '</strong>'; ?></div>
        <div class="panel-body">
        <?php
          echo tep_draw_textarea_field('comments', 'soft', 60, 3, $comments, 'id="inputComments" placeholder="' . TABLE_HEADING_COMMENTS . '"');
        ?>
        </div>
      </div>
    </div>
  </div>

  <div class="clearfix"></div>

  <div class="buttonSet">
    <span class="buttonAction">
      <?php
      if (is_array($payment_modules->modules)) {
        echo $payment_modules->process_button();
      }
      echo tep_draw_button(IMAGE_BUTTON_CONFIRM_ORDER, 'glyphicon glyphicon-ok', null, 'primary');
      ?>
    </span>
  </div>

  <div class="clearfix"></div>

  <div class="contentText">
    <div class="stepwizard">
      <div class="stepwizard-row">
        <div class="stepwizard-step">
          <a href="<?php echo tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'); ?>"><button type="button" class="btn btn-default btn-circle">1</button></a>
          <p><a href="<?php echo tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'); ?>"><?php echo CHECKOUT_BAR_DELIVERY; ?></a></p>
        </div>
        <div class="stepwizard-step">
          <button type="button" class="btn btn-primary btn-circle">2</button>
          <p><?php echo CHECKOUT_BAR_CONFIRMATION; ?></p>
        </div>
      </div>
    </div>
  </div>
  </div>

</div>

</form>

<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
