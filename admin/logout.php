<?php
/**
 * Logout
 */

session_destroy();
header('Location: ../login.php');
exit();
?>
