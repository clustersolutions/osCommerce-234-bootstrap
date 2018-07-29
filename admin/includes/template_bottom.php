<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/
?>
			</main>
		</div>
	</div>
</div>
    <!-- Icons -->
    <script src="https://unpkg.com/feather-icons/dist/feather.min.js"></script>
    <script>
      feather.replace()
    </script>
<script>
	var nowTemp = new Date(); 
	var now = new Date(nowTemp.getFullYear(), nowTemp.getMonth(), nowTemp.getDate(), 0, 0, 0, 0);	
	
	$('#dob').datepicker(
		{
			dateFormat: "<?php echo JQUERY_DATEPICKER_FORMAT; ?>",
			viewMode: 2
		}
	);
	
	$('#dfrom').datepicker(
		{
			dateFormat: "<?php echo JQUERY_DATEPICKER_FORMAT; ?>",
			onRender: function(date) {
				return date.valueOf() > now.valueOf() ? 'disabled' : '';
			}
		}
	);
	$('#dto').datepicker(
		{
			dateFormat: "<?php echo JQUERY_DATEPICKER_FORMAT; ?>",
			onRender: function(date) {
				return date.valueOf() > now.valueOf() ? 'disabled' : '';
			}
		}
	);
</script>
<?php require('includes/footer.php'); ?>

</body>
</html>
