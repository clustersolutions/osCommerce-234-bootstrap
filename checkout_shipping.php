<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');
  require('includes/classes/http_client.php');

// if the customer is not logged on, redirect them to the login page
  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot();
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }

// if there is nothing in the customers cart, redirect them to the shopping cart page
  if ($cart->count_contents() < 1) {
    tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
  }

// Stock Check
  if ( (STOCK_CHECK == 'true') && (STOCK_ALLOW_CHECKOUT != 'true') ) {
    $products = $cart->get_products();
    for ($i=0, $n=sizeof($products); $i<$n; $i++) {
      if (tep_check_stock($products[$i]['id'], $products[$i]['quantity'])) {
        tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
        break;
      }
    }
  }

// if default_address_id is null redirect to address_book_process ttl 121513
  if (tep_default_entry_country_id($customer_default_address_id) == 0) {
    tep_redirect(tep_href_link(FILENAME_ADDRESS_BOOK_PROCESS, '', 'SSL'));
  }

// if no shipping destination address was selected, use the customers own address as default
  if (!tep_session_is_registered('sendto')) {
    tep_session_register('sendto');
    $sendto = $customer_default_address_id;
  } else {
// verify the selected shipping address
    if ( (is_array($sendto) && empty($sendto)) || is_numeric($sendto) ) {
      $check_address_query = tep_db_query("select count(*) as total from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customer_id . "' and address_book_id = '" . (int)$sendto . "'");
      $check_address = tep_db_fetch_array($check_address_query);

      if ($check_address['total'] != '1') {
        $sendto = $customer_default_address_id;
        if (tep_session_is_registered('shipping')) tep_session_unregister('shipping');
      }
    }
  }

// if no billing destination address was selected, use the customers own address as default
  if (!tep_session_is_registered('billto')) {
    tep_session_register('billto');
    $billto = $customer_default_address_id;
  } else {
// verify the selected billing address
    if ( (is_array($billto) && empty($billto)) || is_numeric($billto) ) {
      $check_address_query = tep_db_query("select count(*) as total from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customer_id . "' and address_book_id = '" . (int)$billto . "'");
      $check_address = tep_db_fetch_array($check_address_query);

      if ($check_address['total'] != '1') {
        $billto = $customer_default_address_id;
        if (tep_session_is_registered('payment')) tep_session_unregister('payment');
      }
    }
  }

  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;

// register a random ID in the session to check throughout the checkout procedure
// against alterations in the shopping cart contents
  if (!tep_session_is_registered('cartID')) {
    tep_session_register('cartID');
  } elseif (($cartID != $cart->cartID) && tep_session_is_registered('shipping')) {
    tep_session_unregister('shipping');
  }

  $cartID = $cart->cartID = $cart->generate_cart_id();

// if the order contains only virtual products, forward the customer to the billing page as
// a shipping address is not needed
  if ($order->content_type == 'virtual') {
    if (!tep_session_is_registered('shipping')) tep_session_register('shipping');
    $shipping = false;
    $sendto = false;
    // no redirect billing address is needed. Cluster Solutions. 12-08-2014... tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
  }

  $total_weight = $cart->show_weight();
  $total_count = $cart->count_contents();

// load all enabled payment modules
  require(DIR_WS_CLASSES . 'payment.php');
  $payment_modules = new payment;

// load all enabled shipping modules
  require(DIR_WS_CLASSES . 'shipping.php');
  $shipping_modules = new shipping;

  if ( defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && (MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true') ) {
    $pass = false;

    switch (MODULE_ORDER_TOTAL_SHIPPING_DESTINATION) {
      case 'national':
        if ($order->delivery['country_id'] == STORE_COUNTRY) {
          $pass = true;
        }
        break;
      case 'international':
        if ($order->delivery['country_id'] != STORE_COUNTRY) {
          $pass = true;
        }
        break;
      case 'both':
        $pass = true;
        break;
    }

    $free_shipping = false;

    if ( ($pass == true) && ($order->info['total'] >= MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER) ) {
      $free_shipping = true;

      include(DIR_WS_LANGUAGES . $language . '/modules/order_total/ot_shipping.php');
    }
  } else {
    $free_shipping = false;
  }

// process the selected shipping method
  if ( isset($HTTP_POST_VARS['action']) && ($HTTP_POST_VARS['action'] == 'process') && isset($HTTP_POST_VARS['formid']) && ($HTTP_POST_VARS['formid'] == $sessiontoken) ) {
    if (!tep_session_is_registered('comments')) tep_session_register('comments');
    if (tep_not_null($HTTP_POST_VARS['comments'])) {
      $comments = tep_db_prepare_input($HTTP_POST_VARS['comments']);
    }

    if (!tep_session_is_registered('shipping')) tep_session_register('shipping');

    if ( (tep_count_shipping_modules() > 0) || ($free_shipping == true) ) {
      if ( (isset($HTTP_POST_VARS['shipping'])) && (strpos($HTTP_POST_VARS['shipping'], '_')) ) {
        $shipping = $HTTP_POST_VARS['shipping'];

        list($module, $method) = explode('_', $shipping);
        if ( is_object($$module) || ($shipping == 'free_free') ) {
          if ($shipping == 'free_free') {
            $quote[0]['methods'][0]['title'] = FREE_SHIPPING_TITLE;
            $quote[0]['methods'][0]['cost'] = '0';
          } else {
            $quote = $shipping_modules->quote($method, $module);
          }
          if (isset($quote['error'])) {
            tep_session_unregister('shipping');
          } else {
            if ( (isset($quote[0]['methods'][0]['title'])) && (isset($quote[0]['methods'][0]['cost'])) ) {
              $shipping = array('id' => $shipping,
                                'title' => (($free_shipping == true) ?  $quote[0]['methods'][0]['title'] : $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')'),
                                'cost' => $quote[0]['methods'][0]['cost']);

              tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, (isset($HTTP_POST_VARS['payment']) && tep_not_null($HTTP_POST_VARS['payment']) ? 'payment=' . $HTTP_POST_VARS['payment'] : ''), 'SSL'));
            }
          }
        } else {
          tep_session_unregister('shipping');
        }
      }
    } else {
      if ( defined('SHIPPING_ALLOW_UNDEFINED_ZONES') && (SHIPPING_ALLOW_UNDEFINED_ZONES == 'False') ) {
        tep_session_unregister('shipping');
      } else {
        $shipping = false;
        tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'));
      }
    }
  }

  $selection = $payment_modules->selection();

  $payment_selection_size = sizeof($selection);

// get all available shipping quotes
  $quotes = $shipping_modules->quote();

// if no shipping method has been selected, automatically select the cheapest method.
// if the modules status was changed when none were available, to save on implementing
// a javascript force-selection method, also automatically select the cheapest shipping
// method if more than one module is now enabled
  if ( !tep_session_is_registered('shipping') || ( tep_session_is_registered('shipping') && ($shipping == false) && (tep_count_shipping_modules() > 1) ) ) $shipping = $shipping_modules->cheapest();

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_SHIPPING);

  if ( defined('SHIPPING_ALLOW_UNDEFINED_ZONES') && (SHIPPING_ALLOW_UNDEFINED_ZONES == 'False') && !    tep_session_is_registered('shipping') && ($shipping == false) ) {
  $messageStack->add_session('checkout_address', ERROR_NO_SHIPPING_AVAILABLE_TO_SHIPPING_ADDRESS);
  tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING_ADDRESS, '', 'SSL'));
}

  $breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
  $breadcrumb->add(NAVBAR_TITLE_2, tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));

  require(DIR_WS_INCLUDES . 'template_top.php');
?>

<script><!--
var selected;

function selectRowEffect(object, buttonSelect) {
  if (!selected) {
    if (document.getElementById) {
      selected = document.getElementById('defaultSelected');
    } else {
      selected = document.all['defaultSelected'];
    }
  }

  if (selected) selected.className = 'moduleRow';
  object.className = 'moduleRowSelected';
  selected = object;

// one button is not an array
  if (document.checkout_address.payment[0]) {
    document.checkout_address.payment[buttonSelect].checked=true;
  } else {
    document.checkout_address.payment.checked=true;
  }
}

function rowOverEffect(object) {
  if (object.className == 'moduleRow') object.className = 'moduleRowOver';
}

function rowOutEffect(object) {
  if (object.className == 'moduleRowOver') object.className = 'moduleRow';
}
//--></script>
<?php echo $payment_modules->javascript_validation(); ?>

<div class="page-header">
  <h1><?php echo HEADING_TITLE; ?></h1>
</div>

<?php echo tep_draw_form('checkout_address', tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'), 'post', 'class="form-horizontal" onsubmit="return check_form();"', true) . tep_draw_hidden_field('action', 'process'); ?>

<div class="contentContainer">
  <div class="contentText row">
    <div class="col-sm-6">
      <h2><?php echo TABLE_HEADING_SHIPPING_ADDRESS; ?></h2>
      <div class="panel panel-info equal-height">
        <div class="panel-heading"><?php echo '<strong>' .  TITLE_SHIPPING_ADDRESS . '</strong>' . tep_draw_button(IMAGE_BUTTON_CHANGE_ADDRESS, 'glyphicon glyphicon-home', tep_href_link(FILENAME_CHECKOUT_SHIPPING_ADDRESS, '', 'SSL'), NULL, NULL, 'pull-right btn-info btn-xs' ); ?></div>
        <div class="panel-body">
          <?php echo tep_address_phone_label($customer_id, $sendto, true, ' ', '<br />'); ?>
        </div>
      </div>
    </div>
    <div class="col-sm-6">
      <h2><?php echo TABLE_HEADING_BILLING_ADDRESS; ?></h2>
      <div class="panel panel-warning equal-height">
        <div class="panel-heading"><?php echo '<strong>' . TITLE_BILLING_ADDRESS . '</strong>' . tep_draw_button(IMAGE_BUTTON_CHANGE_ADDRESS, 'glyphicon glyphicon-home', tep_href_link(FILENAME_CHECKOUT_PAYMENT_ADDRESS, '', 'SSL'), NULL, NULL, 'pull-right btn-info btn-xs' ); ?></div>
        <div class="panel-body">
          <?php echo tep_address_phone_label($customer_id, $billto, true, ' ', '<br />'); ?>
        </div>
      </div>
    </div>
    <div class="col-sm-<?php echo ($payment_selection_size == 1 ? '12' : '6' ); ?>">
<?php
  if (tep_count_shipping_modules() > 0) {
?>

      <h2><?php echo TABLE_HEADING_SHIPPING_METHOD; ?></h2>
      <div class="panel panel-info">
        <div class="panel-heading"><?php echo TABLE_HEADING_SHIPPING_METHOD; ?></div>

        <div class="panel-body">

<?php
    if (sizeof($quotes) > 1 && sizeof($quotes[0]) > 1) {
?>

          <div class="alert alert-warning">
            <div class="row">
              <div class="col-xs-8">
                <?php echo TEXT_CHOOSE_SHIPPING_METHOD; ?>
              </div>
              <div class="col-xs-4 text-right">
                <?php echo '<strong>' . TITLE_PLEASE_SELECT . '</strong>'; ?>
              </div>
            </div>
          </div>

<?php
    } elseif ($free_shipping == false) {
?>

          <div class="alert alert-info"><?php echo TEXT_ENTER_SHIPPING_INFORMATION; ?></div>

<?php
    }
?>

          <table class="table table-striped table-condensed table-hover">
            <tbody>

<?php
    if ($free_shipping == true) {
?>

            <div class="panel panel-success">
              <div class="panel-heading"><strong><?php echo FREE_SHIPPING_TITLE; ?></strong>&nbsp;<?php echo $quotes[$i]['icon']; ?></div>
              <div class="panel-body">
                <?php echo sprintf(FREE_SHIPPING_DESCRIPTION, $currencies->format(MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER)) . tep_draw_hidden_field('shipping', 'free_free'); ?>
              </div>
            </div>

<?php
    } else {
      for ($i=0, $n=sizeof($quotes); $i<$n; $i++) {
        for ($j=0, $n2=sizeof($quotes[$i]['methods']); $j<$n2; $j++) {
// set the radio button to be checked if it is the method chosen
          $checked = (($quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'] == $shipping['id']) ? true : false);

          echo '            <tr>' . "\n";

?>

              <td>
                <strong><?php echo $quotes[$i]['module']; ?></strong>
          <?php
          if (isset($quotes[$i]['icon']) && tep_not_null($quotes[$i]['icon'])) echo '&nbsp;' . $quotes[$i]['icon'];
          ?>

          <?php
          if (isset($quotes[$i]['error'])) {
            echo '            <div class="help-block">' . $quotes[$i]['error'] . '</div>';
          }
          ?>

          <?php
          if (tep_not_null($quotes[$i]['methods'][$j]['title'])) echo '<div class="help-block">' . $quotes[$i]['methods'][$j]['title'] . '</div>';
          ?>
              </td>

<?php
            if ( ($n > 1) || ($n2 > 1) ) {
?>

              <td align="right">
          <?php
          if (isset($quotes[$i]['error'])) {
            // nothing
            echo '&nbsp;';
          }
          else {
            echo $currencies->format(tep_add_tax($quotes[$i]['methods'][$j]['cost'], (isset($quotes[$i]['tax']) ? $quotes[$i]['tax'] : 0))); ?>&nbsp;&nbsp;<?php echo tep_draw_radio_field('shipping', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'], $checked, 'required aria-required="true"');
          }
          ?>
              </td>

<?php
            } else {
?>

              <td align="right"><?php echo $currencies->format(tep_add_tax($quotes[$i]['methods'][$j]['cost'], (isset($quotes[$i]['tax']) ? $quotes[$i]['tax'] : 0))) . tep_draw_hidden_field('shipping', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id']); ?></td>

<?php
            }
?>

            </tr>

<?php
        }
      }
    }
?>

            </tbody>
          </table>
        </div>
      </div>

<?php
  }
?>

    </div>
    <div class="col-sm-6">
<?php
  if ($payment_selection_size > 1) {
?>
      <h2><?php echo TABLE_HEADING_PAYMENT_METHOD; ?></h2>
      <div class="panel panel-warning">
        <div class="panel-heading"><?php echo TABLE_HEADING_PAYMENT_METHOD; ?></div>
        <div class="panel-body">

<?php
    if ($payment_selection_size > 1) {
?>

          <div class="contentText">
            <div class="alert alert-warning">
              <div class="row">
                <div class="col-xs-8">
                  <?php echo TEXT_SELECT_PAYMENT_METHOD; ?>
                </div>
                <div class="col-xs-4 text-right">
                  <?php echo '<strong>' . TITLE_PLEASE_SELECT . '</strong>'; ?>
                </div>
              </div>
            </div>
          </div>


<?php
      } else {
?>

          <div class="contentText">
            <div class="alert alert-warning"><?php echo TEXT_ENTER_PAYMENT_INFORMATION; ?></div>
          </div>

<?php
      }
?>

          <div class="contentText">

<?php
    $radio_buttons = 0;
    for ($i=0, $n=sizeof($selection); $i<$n; $i++) {
      if (isset($quotes[$i]['error'])) {
?>
              <div class="contentText">
                <div class="alert alert-warning"><?php echo $selection[$i]['error']; ?></div>
              </div>

<?php
      } else {

?>

            <div class="form-group">
              <label class="control-label col-xs-5"><strong><?php echo (tep_not_null($selection[$i]['image']) ? $selection[$i]['image'] : $selection[$i]['module']); ?></strong></label>
              <div class="col-xs-7 col-xs-pull-1 text-right">
                <label class="checkbox-inline">
            <?php
            if (sizeof($selection) > 1) {
              echo tep_draw_radio_field('payment', $selection[$i]['id'], ($selection[$i]['id'] == $payment));
            } else {
              echo tep_draw_hidden_field('payment', $selection[$i]['id']);
            }
            ?>
                </label>
              </div>
            </div>

<?php
        $radio_buttons++;
      }
    }
?>

          </div>
        </div>
      </div>
<?php
  } elseif ($payment_selection_size == 1) {
    echo tep_draw_hidden_field('payment', $selection[0]['id']);
  }
?>
    </div>
  </div>

  <div class="clearfix"></div>

  <div class="buttonSet">
    <span class="buttonAction"><?php echo tep_draw_button(IMAGE_BUTTON_CONTINUE, 'glyphicon glyphicon-chevron-right', null, 'primary'); ?></span>
  </div>

  <div class="clearfix"></div>

  <div class="contentText">
    <div class="stepwizard">
      <div class="stepwizard-row">
        <div class="stepwizard-step">
          <button type="button" class="btn btn-primary btn-circle">1</button>
          <p><?php echo CHECKOUT_BAR_DELIVERY; ?></p>
        </div>
        <div class="stepwizard-step">
          <button type="button" class="btn btn-default btn-circle" disabled="disabled">2</button>
          <p><?php echo CHECKOUT_BAR_CONFIRMATION; ?></p>
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
