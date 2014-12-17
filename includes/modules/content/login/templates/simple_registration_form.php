<div class="contentContainer <?php echo (MODULE_CONTENT_SIMPLE_REGISTRATION_FORM_CONTENT_WIDTH == 'Half') ? 'col-sm-6' : 'col-sm-12'; ?>">
  <h2><?php echo MODULE_CONTENT_SIMPLE_REGISTRATION_HEADING_NEW_CUSTOMER; ?></h2>

  <div class="contentText">
    <p><?php echo MODULE_CONTENT_SIMPLE_REGISTRATION_TEXT_NEW_CUSTOMER; ?></p>

    <?php echo tep_draw_form('create_account', tep_href_link(FILENAME_LOGIN, '', 'SSL'), 'post', 'class="form-horizontal" onsubmit="return check_form(create_account);"', true) . tep_draw_hidden_field('action', 'process'); ?>
<? /*
      <div class="form-group has-feedback">
        <label for="inputFirstName" class="control-label col-xs-3"><?php echo ENTRY_FIRST_NAME; ?></label>
        <div class="col-xs-9">
          <?php
          echo tep_draw_input_field('firstname', NULL, 'required aria-required="true" id="inputFirstName" placeholder="' . ENTRY_FIRST_NAME . '"');
          echo FORM_REQUIRED_INPUT;
          if (tep_not_null(ENTRY_FIRST_NAME_TEXT)) echo '<span class="help-block">' . ENTRY_FIRST_NAME_TEXT . '</span>';
          ?>
        </div>
      </div>
      <div class="form-group has-feedback">
        <label for="inputLastName" class="control-label col-xs-3"><?php echo ENTRY_LAST_NAME; ?></label>
        <div class="col-xs-9">
          <?php
          echo tep_draw_input_field('lastname', NULL, 'required aria-required="true" id="inputLastName" placeholder="' . ENTRY_LAST_NAME . '"');
          echo FORM_REQUIRED_INPUT;
          if (tep_not_null(ENTRY_LAST_NAME_TEXT)) echo '<span class="help-block">' . ENTRY_LAST_NAME_TEXT . '</span>';
          ?>
        </div>
      </div>
*/?>
      <div class="form-group has-feedback">
        <label for="inputEmail" class="control-label col-xs-3"><?php echo ENTRY_EMAIL_ADDRESS; ?></label>
        <div class="col-xs-9">
          <?php
          echo tep_draw_input_field('email_address', NULL, 'required aria-required="true" id="inputEmail" placeholder="' . ENTRY_EMAIL_ADDRESS . '"');
          echo FORM_REQUIRED_INPUT;
          if (tep_not_null(ENTRY_EMAIL_ADDRESS_TEXT)) echo '<span class="help-block">' . ENTRY_EMAIL_ADDRESS_TEXT . '</span>';
          ?>
        </div>
      </div>
      <div class="form-group has-feedback">
        <label for="inputPassword" class="control-label col-xs-3"><?php echo ENTRY_PASSWORD; ?></label>
        <div class="col-xs-9">
          <?php
          echo tep_draw_password_field('password', NULL, 'required aria-required="true" id="inputPassword" placeholder="' . ENTRY_PASSWORD . '"');
          echo FORM_REQUIRED_INPUT;
          if (tep_not_null(ENTRY_PASSWORD_TEXT)) echo '<span class="help-block">' . ENTRY_PASSWORD_TEXT . '</span>';
          ?>
        </div>
      </div>
      <div class="form-group has-feedback">
        <label for="inputConfirmation" class="control-label col-xs-3"><?php echo ENTRY_PASSWORD_CONFIRMATION; ?></label>
        <div class="col-xs-9">
          <?php
          echo tep_draw_password_field('confirmation', NULL, 'required aria-required="true" id="inputConfirmation" placeholder="' . ENTRY_PASSWORD_CONFIRMATION . '"');
          echo FORM_REQUIRED_INPUT;
          if (tep_not_null(ENTRY_PASSWORD_CONFIRMATION_TEXT)) echo '<span class="help-block">' . ENTRY_PASSWORD_CONFIRMATION_TEXT . '</span>';
          ?>
        </div>
      </div>
      <p class="text-right"><?php echo tep_draw_button(IMAGE_BUTTON_CONTINUE, 'glyphicon glyphicon-user', null, 'primary'); ?></p>

    </form>
  </div>
</div>
