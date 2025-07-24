<?php
$new_password = '123456'; // <--- Choose a strong password here
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
echo $hashed_password;
?>