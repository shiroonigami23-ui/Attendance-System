<?php
require_once '../includes/Config.php';
session_destroy();
header('Location: ../login.php?msg=Logged+out+successfully');
exit();
