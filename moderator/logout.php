<?php
/**
 * Выход модератора — /moderator/logout
 */

unset($_SESSION['moderator_id']);
unset($_SESSION['moderator_username']);
session_destroy();
header('Location: /moderator/login');
exit;
