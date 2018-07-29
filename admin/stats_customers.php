<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  require('includes/classes/currencies.php');
  $currencies = new currencies();

  require('includes/template_top.php');
?>
<div class="page-header">
	<h1><?php echo HEADING_TITLE; ?></h1>
</div>
<div class="row">
	<div class="col-md-8">	
		<table class="table table-bordered table-striped table-hover">
				<thead>
					<tr class="dataTableHeadingRow">
						<th class="dataTableHeadingContent"><?php echo TABLE_HEADING_NUMBER; ?></th>
						<th class="dataTableHeadingContent"><?php echo TABLE_HEADING_CUSTOMERS; ?></th>
						<th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TOTAL_PURCHASED; ?>&nbsp;</th>
					</tr>
				</thead>
<?php
  if (isset($_GET['page']) && ($_GET['page'] > 1)) $rows = $_GET['page'] * MAX_DISPLAY_SEARCH_RESULTS - MAX_DISPLAY_SEARCH_RESULTS;
  $customers_query_raw = "select c.customers_firstname, c.customers_lastname, sum(op.products_quantity * op.final_price) as ordersum from " . TABLE_CUSTOMERS . " c, " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_ORDERS . " o where c.customers_id = o.customers_id and o.orders_id = op.orders_id group by c.customers_firstname, c.customers_lastname order by ordersum DESC";
  $customers_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $customers_query_raw, $customers_query_numrows);
// fix counted customers
  $customers_query_numrows = tep_db_query("select customers_id from " . TABLE_ORDERS . " group by customers_id");
  $customers_query_numrows = tep_db_num_rows($customers_query_numrows);

  $rows = 0;
  $customers_query = tep_db_query($customers_query_raw);
  while ($customers = tep_db_fetch_array($customers_query)) {
    $rows++;

    if (strlen($rows) < 2) {
      $rows = '0' . $rows;
    }
?>
			<tr class="dataTableRow" onclick="document.location.href='<?php echo tep_href_link('customers.php', 'search=' . $customers['customers_lastname']); ?>'">
				<td class="dataTableContent"><?php echo $rows; ?>.</td>
				<td class="dataTableContent"><?php echo '<a href="' . tep_href_link('customers.php', 'search=' . $customers['customers_lastname']) . '">' . $customers['customers_firstname'] . ' ' . $customers['customers_lastname'] . '</a>'; ?></td>
				<td class="dataTableContent" align="right"><?php echo $currencies->format($customers['ordersum']); ?>&nbsp;</td>
			</tr>
<?php
  }
?>
		</table>
		<nav>
			<ul class="pagination float-left"><?php echo $customers_split->display_count($customers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_CUSTOMERS); ?></ul>
			<?php echo $customers_split->display_links($customers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?>
		</nav>
	</div>

<?php
  echo '</div>';//row end
  
  require('includes/template_bottom.php');
  require('includes/application_bottom.php');
?>
