# To-Do List PHP App

A PHP and MySQL todo list application with user registration, login, profile
avatars, task priorities, task dates, filtering, task completion, and editable
notes.

This project was created for the Web Technologies and Programming course at
university.

## Features

- User registration and login with hashed passwords
- Optional "remember me" token support
- Profile image upload with a default avatar fallback
- Create, edit, complete, delete, search, and filter tasks
- Priority levels: Low, Medium, and High
- Per-task notes with auto-save
- Glass-style responsive interface with Bootstrap and Font Awesome

## Requirements

- PHP 8 or newer
- MySQL or MariaDB
- A local web server such as XAMPP, WAMP, MAMP, or Apache with PHP enabled
- PDO MySQL extension enabled

## Setup

1. Clone or copy this repository into your web server document root.
   For XAMPP on Windows, a common path is:

   ```powershell
   C:\xampp\htdocs\To-Do-List
   ```

2. Start Apache and MySQL.

3. Create the database and tables by importing `database.sql` in phpMyAdmin, or
   from the MySQL command line:

   ```powershell
   mysql -u root -p < database.sql
   ```

4. Check the database settings in `config.php`.
   The default values are:

   ```php
   $host = 'localhost';
   $dbname = 'todo_app';
   $username = 'root';
   $password = '';
   ```

5. Open the app in your browser:

   ```text
   http://localhost/To-Do-List/login.php
   ```

6. Create an account, upload a profile picture, and start adding tasks.

## Project Structure

```text
.
|-- add.php
|-- config.php
|-- database.sql
|-- delete.php
|-- edit.php
|-- index.php
|-- login.php
|-- logout.php
|-- register.php
|-- style.css
|-- update.php
|-- assets/
|   `-- background.gif
`-- uploads/
    `-- default.png
```

## Notes

- Runtime profile uploads are ignored by git so user images are not committed.
  The default avatar is kept in `uploads/default.png`.
- The remember-me cookie is configured as `Secure`, so it is intended for HTTPS
  deployments. On plain local HTTP, the regular login session still works.
- This is a student/local project configuration. Change database credentials and
  deployment settings before using it on a public server.
