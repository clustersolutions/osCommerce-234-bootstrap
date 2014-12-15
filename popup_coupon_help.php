<?php
/*
  $Id: popup_coupon_help.php,v 1.1.2.5 2003/05/02 01:43:29 wilt Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  $navigation->remove_current_page();

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_POPUP_COUPON_HELP);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
<title><?php echo tep_output_string_protected($oscTemplate->getTitle()); ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>" />
<link rel="stylesheet" type="text/css" href="ext/jquery/ui/redmond/jquery-ui-1.8.6.css" />
<script type="text/javascript" src="ext/jquery/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="ext/jquery/ui/jquery-ui-1.8.6.min.js"></script>

<?php
  if (tep_not_null(JQUERY_DATEPICKER_I18N_CODE)) {
?>
<script type="text/javascript" src="ext/jquery/ui/i18n/jquery.ui.datepicker-<?php echo JQUERY_DATEPICKER_I18N_CODE; ?>.js"></script>
<script type="text/javascript">
$.datepicker.setDefaults($.datepicker.regional['<?php echo JQUERY_DATEPICKER_I18N_CODE; ?>']);
</script>
<?php
  }
?>

<script type="text/javascript" src="ext/jquery/bxGallery/jquery.bxGallery.1.1.min.js"></script>
<link rel="stylesheet" type="text/css" href="ext/jquery/fancybox/jquery.fancybox-1.3.4.css" />
<script type="text/javascript" src="ext/jquery/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<link rel="stylesheet" type="text/css" href="ext/960gs/<?php echo ((stripos(HTML_PARAMS, 'dir="rtl"') !== false) ? 'rtl_' : ''); ?>960_24_col.css" />
<link rel="stylesheet" type="text/css" href="stylesheet.css" />
<?php echo $oscTemplate->getBlocks('header_tags'); ?>
</head>
<body>

<?php
  function buildInfobox($header, $contents){
	  global $action;
		$info_box_contents = array();
	  if(isset($action) && tep_not_null($action))
			$info_box_contents[] = array('text' => utf8_encode($header));
	  else
			$info_box_contents[] = array('text' => ($header));
	  new infoBoxHeading($info_box_contents, false, false);

	  $info_box_contents = array();

		if(isset($action) && tep_not_null($action))
			$info_box_contents[] = array('text' => utf8_encode($contents));
		else
			$info_box_contents[] = array('text' => ($contents));
	  new infoBox($info_box_contents);
  }

// v5.13: security flaw fixed in query
//  $coupon_query = tep_db_query("select * from " . TABLE_COUPONS . " where coupon_id = '" . $HTTP_GET_VARS['cID'] . "'");
  $coupon_query = tep_db_query("select * from " . TABLE_COUPONS . " where coupon_id = '" . intval($HTTP_GET_VARS['cID']) . "'");
  $coupon = tep_db_fetch_array($coupon_query);
  $coupon_desc_query = tep_db_query("select * from " . TABLE_COUPONS_DESCRIPTION . " where coupon_id = '" . $HTTP_GET_VARS['cID'] . "' and language_id = '" . $languages_id . "'");
  $coupon_desc = tep_db_fetch_array($coupon_desc_query);
  $text_coupon_help = TEXT_COUPON_HELP_HEADER;
  $text_coupon_help .= sprintf(TEXT_COUPON_HELP_NAME, $coupon_desc['coupon_name']);
  if (tep_not_null($coupon_desc['coupon_description'])) $text_coupon_help .= sprintf(TEXT_COUPON_HELP_DESC, $coupon_desc['coupon_description']);
  $coupon_amount = $coupon['coupon_amount'];
  switch ($coupon['coupon_type']) {
    case 'F':
    $text_coupon_help .= sprintf(TEXT_COUPON_HELP_FIXED, $currencies->format($coupon['coupon_amount']));
    break;
    case 'P':
    $text_coupon_help .= sprintf(TEXT_COUPON_HELP_FIXED, number_format($coupon['coupon_amount'],2). '%');
    break;
    case 'S':
    $text_coupon_help .= TEXT_COUPON_HELP_FREESHIP;
    break;
    default:
  }
  if ($coupon['coupon_minimum_order'] > 0 ) $text_coupon_help .= sprintf(TEXT_COUPON_HELP_MINORDER, $currencies->format($coupon['coupon_minimum_order']));
  $text_coupon_help .= sprintf(TEXT_COUPON_HELP_DATE, tep_date_short($coupon['coupon_start_date']),tep_date_short($coupon['coupon_expire_date']));
  $text_coupon_help .= '<b>' . TEXT_COUPON_HELP_RESTRICT . '</b>';
  $text_coupon_help .= '<br><br>' .  TEXT_COUPON_HELP_CATEGORIES;
  $coupon_get=tep_db_query("select restrict_to_categories from " . TABLE_COUPONS . " where coupon_id='".$HTTP_GET_VARS['cID']."'");
  $get_result=tep_db_fetch_array($coupon_get);

  $cat_ids = split("[,]", $get_result['restrict_to_categories']);
  for ($i = 0; $i < count($cat_ids); $i++) {
    $result = tep_db_query("SELECT * FROM categories, categories_description WHERE categories.categories_id = categories_description.categories_id and categories_description.language_id = '" . $languages_id . "' and categories.categories_id='" . $cat_ids[$i] . "'");
    if ($row = tep_db_fetch_array($result)) {
    $cats .= '<br>' . $row["categories_name"];
    }
  }
  if ($cats=='') $cats = '<br>NONE';
  $text_coupon_help .= $cats;
  $text_coupon_help .= '<br><br>' .  TEXT_COUPON_HELP_PRODUCTS;
  $coupon_get=tep_db_query("select restrict_to_products from " . TABLE_COUPONS . "  where coupon_id='".$HTTP_GET_VARS['cID']."'");
  $get_result=tep_db_fetch_array($coupon_get);

  $pr_ids = split("[,]", $get_result['restrict_to_products']);
  for ($i = 0; $i < count($pr_ids); $i++) {
    $result = tep_db_query("SELECT * FROM products, products_description WHERE products.products_id = products_description.products_id and products_description.language_id = '" . $languages_id . "'and products.products_id = '" . $pr_ids[$i] . "'");
    if ($row = tep_db_fetch_array($result)) {
      $prods .= '<br>' . $row["products_name"];
    }
  }
  if ($prods=='') $prods = '<br>NONE';
  $text_coupon_help .= $prods;
  $text_coupon_help .= '<div style="width:100%; margin:auto; text-align: right;"><a href="#" onclick="window.close(); return false;">' . TEXT_CLOSE_WINDOW . '</a></div>';
?>
<?php
$header = HEADING_COUPON_HELP;
buildInfobox($header, $text_coupon_help);
?>
</body>
</html>
<?php require('includes/application_bottom.php'); ?>
