<div class="panel panel-default">
  <div class="panel-heading"><a href="<?php echo tep_href_link('shopping_cart.php'); ?>"><?php echo MODULE_BOXES_SHOPPING_CART_BOX_TITLE; ?></a></div>
  <div class="panel-body">
    <ul class="shoppingCartList">
      <?php echo $gv_contents_string . $cart_contents_string; ?>
    </ul>
  </div>
</div>
