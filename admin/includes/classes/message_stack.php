<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License

  Example usage:

  $messageStack = new messageStack();
  $messageStack->add('Error: Error 1', 'error');
  $messageStack->add('Error: Error 2', 'warning');
  if ($messageStack->size > 0) echo $messageStack->output();
*/

  class messageStack extends tableBlock {
    var $size = 0;
		
	function __construct() {
      global $messageToStack;

      $this->messages = array();

      if (tep_session_is_registered('messageToStack')) {
        for ($i = 0, $n = sizeof($messageToStack); $i < $n; $i++) {
          $this->add($messageToStack[$i]['text'], $messageToStack[$i]['type']);
        }
        tep_session_unregister('messageToStack');
      }
    }
// class methods
    function add($message, $type = 'error') {
      if ($type == 'error') {
        $this->messages[] = array('params' => 'class="alert alert-danger alert-dismissible"', 'text' => $message);
      } elseif ($type == 'warning') {
        $this->messages[] = array('params' => 'class="alert alert-warning alert-dismissible"', 'text' => $message);
      } elseif ($type == 'success') {
        $this->messages[] = array('params' => 'class="alert alert-success alert-dismissible"', 'text' => $message);
      } else {
        $this->messages[] = array('params' => 'class="alert alert-info alert-dismissible"', 'text' => $message);
      }
      $this->size++;
    }
    function add2($message, $type = 'error') {
      if ($type == 'error') {
        $this->messages[] = array('params' => 'class="messageStackError"', 'text' => tep_image('images/icons/error.gif', ICON_ERROR) . '&nbsp;' . $message);
      } elseif ($type == 'warning') {
        $this->messages[] = array('params' => 'class="messageStackWarning"', 'text' => tep_image('images/icons/warning.gif', ICON_WARNING) . '&nbsp;' . $message);
      } elseif ($type == 'success') {
        $this->messages[] = array('params' => 'class="messageStackSuccess"', 'text' => tep_image('images/icons/success.gif', ICON_SUCCESS) . '&nbsp;' . $message);
      } else {
        $this->messages[] = array('params' => 'class="messageStackError"', 'text' => $message);
      }

      $this->size++;
    }

    function add_session($message, $type = 'error') {
      global $messageToStack;

      if (!tep_session_is_registered('messageToStack')) {
        tep_session_register('messageToStack');
        $messageToStack = array();
      }

      $messageToStack[] = array('text' => $message, 'type' => $type);
    }

    function reset() {
      $this->messages = array();
      $this->size = 0;
    }

    function output() {
      return $this->alertBlock($this->messages);
    }
  }
?>
