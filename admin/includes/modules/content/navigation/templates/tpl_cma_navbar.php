<nav class="navbar navbar-dark navbar-expand-lg fixed-top bg-dark shadow">
  <?php echo '<a class="navbar-brand col-sm-3 col-md-2 pl-0" href="' . tep_href_link('index.php') . '">' . tep_image('images/oscommerce-inverse.png', 'osCommerce Online Merchant v' . tep_get_version(), null, null, 'class="d-inline-block align-top"') . '</a>'; ?>
    <?php
    if ($oscTemplate->hasBlocks('navbar_modules_home')) {
      echo $oscTemplate->getBlocks('navbar_modules_home');
    }
    ?> 

  <div class="navbar-collapse collapse justify-content-stretch" id="navbarSupportedContent">
      <?php
      if ($oscTemplate->hasBlocks('navbar_modules_left')) {
        echo '<ul class="navbar-nav">' . PHP_EOL;
        echo $oscTemplate->getBlocks('navbar_modules_left');
        echo '</ul>' . PHP_EOL;
      }
      if ($oscTemplate->hasBlocks('navbar_modules_right')) {
        echo '<ul class="navbar-nav ml-auto">' . PHP_EOL;
        echo $oscTemplate->getBlocks('navbar_modules_right');
        echo '</ul>' . PHP_EOL;
      }
      ?>
  </div>
</nav>
