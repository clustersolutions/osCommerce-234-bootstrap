<?php
/*
  $Id: validation.php v1.1 2009-03-16 12:52:16Z hpdl $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/
  if ($is_read_only == false ) {
    $sql = "DELETE FROM " . TABLE_ANTI_ROBOT_REGISTRATION . " WHERE timestamp < '" . (time() - 3600) . "' OR session_id = '" . tep_session_id() . "'";
    if( !$result = tep_db_query($sql) ) { die('Could not delete validation key'); }
    $reg_key = gen_reg_key();
    $sql = "INSERT INTO ". TABLE_ANTI_ROBOT_REGISTRATION . " VALUES ('" . tep_session_id() . "', '" . $reg_key . "', '" . time() . "')";
    if( !$result = tep_db_query($sql) ) { die('Could not check registration information'); }
    $check_anti_robotreg_query = tep_db_query("select session_id, reg_key, timestamp from anti_robotreg where session_id = '" . tep_session_id() . "'");
    $new_guery_anti_robotreg = tep_db_fetch_array($check_anti_robotreg_query);
    if (empty($new_guery_anti_robotreg['session_id'])) echo 'Error, unable to read session id.';
    $validation_images = tep_image_captcha('validation_png.php?rsid=' . $new_guery_anti_robotreg['session_id'] .'&csh='.uniqid(0), 'name="Captcha" vspace="10" border="1"');
    if ($validated == CODE_CHECKED && strlen($validated) && 1 == 2) { //added 1 == 2 no need for passed...
      echo VALIDATED . tep_draw_hidden_field('validated',CODE_CHECKED); 
    } else { 
      echo $validation_images . ' <br> ' . tep_draw_input_field('antirobotreg', NULL, 'required aria-required="true" id="inputAntiRobot" placeholder="' . ENTRY_ANTIROBOTREG . '"', '', false);// . ' ' . ($entry_antirobotreg_error ? '<br><font color="red">' . ENTRY_ANTIROBOTREG_TEXT . ' ' . $text_antirobotreg_error . '</font><br>' : '<br><font color="red">' . ENTRY_ANTIROBOTREG_TEXT . '</font> ' . ENTRY_ANTIROBOTREG);
    }
  }
?>
