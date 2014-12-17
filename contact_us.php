<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

// BOF Anti Robot Registration v3.0
  if (ACCOUNT_VALIDATION == 'true' && CONTACT_US_VALIDATION == 'true') {
    require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ACCOUNT_VALIDATION);
    include_once('includes/functions/' . FILENAME_ACCOUNT_VALIDATION);
  }
// EOF Anti Robot Registration v3.0

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CONTACT_US);

  if (isset($HTTP_GET_VARS['action']) && ($HTTP_GET_VARS['action'] == 'send') && isset($HTTP_POST_VARS['formid']) && ($HTTP_POST_VARS['formid'] == $sessiontoken)) {
    $error = false;

    $name = tep_db_prepare_input($HTTP_POST_VARS['name']);
    $email_address = tep_db_prepare_input($HTTP_POST_VARS['email']);
    $enquiry = tep_db_prepare_input($HTTP_POST_VARS['enquiry']);

    if (!tep_validate_email($email_address)) {
      $error = true;

      $messageStack->add('contact', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
    }

// BOF Anti Robot Registration v3.0
    if (ACCOUNT_VALIDATION == 'true' && CONTACT_US_VALIDATION == 'true') {
      if ($entry_antirobotreg_error == true) $error = true;
    }
// EOF Anti Robot Registration v3.0

    $actionRecorder = new actionRecorder('ar_contact_us', (tep_session_is_registered('customer_id') ? $customer_id : null), $name);
    if (!$actionRecorder->canPerform()) {
      $error = true;

      $actionRecorder->record(false);

      $messageStack->add('contact', sprintf(ERROR_ACTION_RECORDER, (defined('MODULE_ACTION_RECORDER_CONTACT_US_EMAIL_MINUTES') ? (int)MODULE_ACTION_RECORDER_CONTACT_US_EMAIL_MINUTES : 15)));
    }

// BOF Anti Robotic Registration v3.0
    if (ACCOUNT_VALIDATION == 'true' && CONTACT_US_VALIDATION == 'true') {
      include(DIR_WS_MODULES . FILENAME_CHECK_VALIDATION);
      if ($entry_antirobotreg_error == true) $messageStack->add('contact', $text_antirobotreg_error);
    }
// EOF Anti Robotic Registration v3.0

    if ($error == false) {
      tep_mail(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, EMAIL_SUBJECT, $enquiry, $name, $email_address);

      $actionRecorder->record();

      tep_redirect(tep_href_link(FILENAME_CONTACT_US, 'action=success'));
    }
  }

// BOF Anti Robot Registration v3.0
  header('cache-control: no-store, no-cache, must-revalidate');
  header("Pragma: no-cache");
// EOF Anti Robotic Registration v3.0

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_CONTACT_US));

  require(DIR_WS_INCLUDES . 'template_top.php');
?>

<div class="page-header">
  <h1><?php echo HEADING_TITLE; ?></h1>
</div>

<?php
  if ($messageStack->size('contact') > 0) {
    echo $messageStack->output('contact');
  }

  if (isset($HTTP_GET_VARS['action']) && ($HTTP_GET_VARS['action'] == 'success')) {
?>

<div class="contentContainer">
  <div class="contentText">
    <div class="alert alert-info"><?php echo TEXT_SUCCESS; ?></div>
  </div>

  <div class="pull-right">
    <?php echo tep_draw_button(IMAGE_BUTTON_CONTINUE, 'glyphicon glyphicon-chevron-right', tep_href_link(FILENAME_DEFAULT)); ?>
  </div>
</div>

<?php
  } else {
?>

<?php echo tep_draw_form('contact_us', tep_href_link(FILENAME_CONTACT_US, 'action=send'), 'post', 'class="form-horizontal"', true); ?>

<div class="contentContainer">
  <div class="contentText">
  
    <p class="inputRequirement text-right"><?php echo FORM_REQUIRED_INFORMATION; ?></p>
    <div class="clearfix"></div>

    <div class="form-group has-feedback">
      <label for="inputFromName" class="control-label col-xs-3"><?php echo ENTRY_NAME; ?></label>
      <div class="col-xs-9">
        <?php
        echo tep_draw_input_field('name', NULL, 'required autofocus="autofocus" aria-required="true" id="inputFromName" placeholder="' . ENTRY_NAME . '"');
        echo FORM_REQUIRED_INPUT;
        ?>
      </div>
    </div>
    <div class="form-group has-feedback">
      <label for="inputFromEmail" class="control-label col-xs-3"><?php echo ENTRY_EMAIL; ?></label>
      <div class="col-xs-9">
        <?php
        echo tep_draw_input_field('email', NULL, 'required aria-required="true" id="inputFromEmail" placeholder="' . ENTRY_EMAIL . '"');
        echo FORM_REQUIRED_INPUT;
        ?>
      </div>
    </div>
    <div class="form-group has-feedback">
      <label for="inputEnquiry" class="control-label col-xs-3"><?php echo ENTRY_ENQUIRY; ?></label>
      <div class="col-xs-9">
        <?php
        echo tep_draw_textarea_field('enquiry', 'soft', 50, 15, NULL, 'required aria-required="true" id="inputEnquiry" placeholder="' . ENTRY_ENQUIRY . '"');
        echo FORM_REQUIRED_INPUT;
        ?>
      </div>
    </div>
<!-- // BOF Anti Robot Registration v3.0-->
<?php
  if (ACCOUNT_VALIDATION == 'true' && strstr($PHP_SELF,'contact_us') &&  CONTACT_US_VALIDATION == 'true') {
?>
    <div class="form-group has-feedback">
      <div class="col-xs-9 col-md-3 pull-right">
        <?php echo FORM_REQUIRED_INPUT; include(DIR_WS_MODULES . FILENAME_DISPLAY_VALIDATION); ?>
      </div>
    </div>
<?php
  }
?>
<!-- // EOF Anti Robot Registration v3.0-->
  </div>
  <div class="buttonSet">
    <span class="buttonAction"><?php echo tep_draw_button(IMAGE_BUTTON_CONTINUE, 'glyphicon glyphicon-send', null, 'primary'); ?></span>
  </div>
</div>

</form>

<?php
  }

  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
