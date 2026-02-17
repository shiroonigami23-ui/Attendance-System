<?php
session_start();
session_destroy();
header('Location: login.php?msg=Logged+out+successfully');
exit();
?>