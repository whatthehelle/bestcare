<?php
session_start();
session_destroy();
header('Location: receptionist_login.php');
exit;
?>