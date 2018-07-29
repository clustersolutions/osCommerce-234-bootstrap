<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <?php echo (tep_session_is_registered('admin')) ? sprintf(MODULE_ADMIN_NAVBAR_ACCOUNT_LOGGED_IN, $admin['username']) : MODULE_ADMIN_NAVBAR_ACCOUNT_LOGGED_OUT; ?>
    </a>
    
    <div class="dropdown-menu dropdown-menu-<?php echo strtolower(MODULE_ADMIN_NAVBAR_ACCOUNT_CONTENT_PLACEMENT); ?>" aria-labelledby="navbarDropdown">
        <?php
        if (tep_session_is_registered('admin')) {

            echo '<a class="dropdown-item" href="' . tep_href_link('login.php', 'action=logoff') . '">' . MODULE_ADMIN_NAVBAR_ACCOUNT_LOGOFF . '</a>';
        }else{
            echo '<a class="dropdown-item" href="' . tep_href_link('login.php') . '">' . MODULE_ADMIN_NAVBAR_ACCOUNT_LOGIN . '</a>';
        }
        ?>
    </div>
</li>