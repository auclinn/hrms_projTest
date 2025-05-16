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

***On Local machine***  | ***On WSL***
:---: | :---:
xampp, phpMyAdmin       | php (ver 8.3.6 or higher)
vscode | vscode (with WSL extension)                   
git bash                | mysql


## To clone this project 
*On local machine:*
- go to your local xampp folder and find ```htdocs``` folder
- right click, then ```Open Git Bash here```
- inside git bash, run: ```git clone https://github.com/auclinn/hrms_projTest.git```

*On WSL:*
- clone the repository on your prefered directory

## **If downloaded as zip file*
- extract the file and move into ```htdocs``` folder

## To create the database 
*On local machine (xampp/phpmyAdmin):*
- go to ```config.php``` and change ```DB_PORT``` to ```3306```
- in the same file, configure ```DB_USER``` and ```DB_PASS``` to your own credentials
- copy and run the database and table creation from ```hrms_db.sql``` to SQL in phpMyAdmin
- for the insertion of users, follow instructions from the same file

*On WSL (mysql):*
- configure credentials in ```config.php``` similar to local machine instructions
- in WSL, open mysql using your credentials
- copy and run db creation from ```hrms_db.sql``` in mysql
- *optional:* query showing of all tables to check if creations were successful
- same instructions for user insertion as above in local machine instructions 

## To run the project
- open the project in VSCode, have the terminal open (like bash)
- in terminal, run: ```php -S localhost:8000```
- if that results in a command not found error, run this instead:  ```"C:/xampp/php/php.exe" -S localhost:8000```

---
## Library used
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) 