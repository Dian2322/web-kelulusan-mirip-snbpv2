<?php
require_once '../config.php';

// landing page for admin directory: redirect based on login status
if (is_admin_logged_in()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;