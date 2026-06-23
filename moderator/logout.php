<?php
/**
 * Выход модератора — /moderator/logout
 */

session_destroy();
header('Location: /');
exit;
