#  Group 4 
---
## Members
- Leader: Awatin, Bernadette
- Adaya, Angelika 
- Contreras, Melvin
- Laylo, Jake
---
## Project: HRMS System (PHP)

---
## For this project, you need:

*On Local machine*  | *On WSL*
------------------- | -------------
xampp, phpMyAdmin   | php (ver 8.3.6 or higher)
vscode              | mysql



## To clone this project 
- go to your local xampp folder and find ```htdocs``` folder
- right click, then ```Open Git Bash here```
- inside git bash, run: ```git clone https://github.com/auclinn/hrms_projTest.git```

## To create the database (mysql/phpmyadmin)
- go to ```config.php``` and change ```DB_PORT``` to ```3306```
- copy and run the database and table creation from ```hrms_db.sql``` to SQL in phpMyAdmin
- for the insertion of users, follow instructions from the same file

## To run the project
- open the project in VSCode, have the terminal open (like bash)
- in terminal, run: ```php -S localhost:8000```
- if that results in a command not found error, run this instead:  ```"C:/xampp/php/php.exe" -S localhost:8000```

---
## Library
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) 