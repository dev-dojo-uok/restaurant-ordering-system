<?php
require_once '../app/helpers/auth.php';

logout();

header('Location: login.php?message=logged_out');
exit;
