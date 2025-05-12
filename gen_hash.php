<?php
// admin pw = admin123
// hr pw = hr123
// employee pw = emp2

//i cant remember the passwords i made for user templates so here is the script
$plainPassword1 = 'admin123';
$hashedPassword1 = password_hash($plainPassword1, PASSWORD_DEFAULT);

$plainPassword2 = 'hr123'; 
$hashedPassword2 = password_hash($plainPassword2, PASSWORD_DEFAULT);

$plainPassword3 = 'emp2';
$hashedPassword3 = password_hash($plainPassword3, PASSWORD_DEFAULT);

echo "Admin password: " . $hashedPassword1;
echo "\n";
echo "HR password: " . $hashedPassword2;
echo "\n";
echo "Employee password: " . $hashedPassword3;

//THEN RUN on terminal (git bash): 
//   php gen_hash.php (or "C:/xampp/php/php.exe" gen_hash.php if mag error ng php command not found)
?>

