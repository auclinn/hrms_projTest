<?php
// admin pw = admin123
// hr pw = hr123
// employee pw = emp2

//i cant remember the passwords i made for user templates so here is the script kekw
$plainPassword = 'emp123'; //change to check hashed pw to see if same sha sa db
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

echo "Hashed password: " . $hashedPassword;

//php gen_hash.php to see the hashed password then compare it to the db
?>

