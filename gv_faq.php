<?php
/*
  $Id: gv_faq.php,v 1.2 2003/02/17 23:53:04 wilt Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2002 - 2003 osCommerce

  Gift Voucher System v1.0
  Copyright (c) 2001, 2002 Ian C Wilson
  http://www.phesis.org

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_GV_FAQ);

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(GV_FAQ));
  require(DIR_WS_INCLUDES . 'template_top.php');
?>
<h1><?php echo HEADING_TITLE; ?></h1>

<script>
	$(function() {
		$( "#divFAQ" ).accordion({
			autoHeight: false,
			navigation: true,
			collapsible: true	
		});
	});
</script>

<div class="contentContainer">
  <div class="contentText">
    <div id="divFAQ">
        <h3><a href="#">Purchasing Gift Vouchers/Store Credit</a></h3>
        <div>
            <p>
                Gift Vouchers/Store Credit are purchased just like any other item in our store. You can 
                pay for them using the stores standard payment method(s).
                Once purchased the value of the Gift Voucher will be added to your own personal 
                <?php echo BOX_HEADING_GIFT_VOUCHER; ?>. If you have funds in your <?php echo BOX_HEADING_GIFT_VOUCHER; ?>, you will 
                notice that the amount now shows in the Shopping Cart box, and also provides a 
                link to a page where you can send the Gift Voucher to some one via email.
            </p>
        </div>
        <h3><a href="#">How to send Gift vouchers</a></h3>
        <div>
            <p>
                To send a Gift Voucher that you have purchased, you need to go to our Send Gift Voucher Page. You can
                find the link to this page in the Shopping Cart Box in the right hand column of 
                each page.
                When you send a Gift Voucher, you need to specify the following:<br> <br>
                The name of the person you are sending the Gift Voucher to.<br>
                The email address of the person you are sending the Gift Voucher to.<br>
                The amount you want to send. (Note you don\'t have to send the full amount that 
                is in your Gift Voucher Account.) <br>
                A short message which will apear in the email.<br><br>
                Please ensure that you have entered all of the information correctly, although 
                you will be given the opportunity to change this as much as you want before 
                the email is actually sent.		
            </p>
        </div>  
        <h3><a href="#">Buying with Gift Vouchers/Store Credit</a></h3>
        <div>
            <p>
                If you have funds in your <?php echo BOX_HEADING_GIFT_VOUCHER; ?>, you can use those funds to 
                purchase other items in our store. At the checkout stage, an extra box will
                appear. Clicking this box will apply those funds in your <?php echo BOX_HEADING_GIFT_VOUCHER; ?>.
                Please note, you will still have to select another payment method if there 
                is not enough in your <?php echo BOX_HEADING_GIFT_VOUCHER; ?> to cover the cost of your purchase. 
                If you have more funds in your <?php echo BOX_HEADING_GIFT_VOUCHER; ?> than the total cost of 
                your purchase the balance will be left in you <?php echo BOX_HEADING_GIFT_VOUCHER; ?> for the
                future.
            </p>
        </div>    
        <h3><a href="#">Redeeming Gift Vouchers</a></h3>
        <div>
            <p>
                If you receive a Gift Voucher by email it will contain details of who sent 
                you the Gift Voucher, along with possibly a short message from them. The Email 
                will also contain the Gift Voucher Number. It is probably a good idea to print 
                out this email for future reference. You can now redeem the Gift Voucher in 
                two ways:<br>
                <ul><li>
                By clicking on the link contained within the email for this express purpose.
                This will take you to the store\'s Redeem Voucher page. you will the be requested 
                to create an account, before the Gift Voucher is validated and placed in your 
                Gift Voucher Account ready for you to spend it on whatever you want.<br/>
                </li><li>
                During the checkout procces, on the same page that you select a payment method 
                there will be a box to enter a Redeem Code. Enter the code here, and click the redeem button. The code will be
                validated and added to your Gift Voucher account. You Can then use the amount to purchase any item from our store.
                </li></ul>
            </p>
        </div>  
        <h3><a href="#">When problems occur</a></h3>
        <div>
            <p>
                For any queries regarding the Gift Voucher System, please contact the store 
                by email at '<?php echo STORE_OWNER_EMAIL_ADDRESS; ?>'. 
                <p>Please make sure you give as much information as possible in the email.</p>
            </p>
        </div>  
               
    </div>
  </div>

  <div class="buttonSet">
    <span class="buttonAction"><?php echo tep_draw_button(IMAGE_BUTTON_CONTINUE, 'triangle-1-e', tep_href_link(FILENAME_DEFAULT)); ?></span>
  </div>
</div>

<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
