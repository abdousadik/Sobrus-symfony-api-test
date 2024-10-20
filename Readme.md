# Blog Article API

## Overview

The Blog Article API is a Symfony-based RESTful API that allows users to manage blog articles. This API includes features for creating, updating, retrieving, and deleting articles, with built-in JWT (JSON Web Token) security for authentication.

## Features

- Create, read, update, and delete blog articles
- Soft delete functionality for blog articles
- JWT authentication for secure API access

## Technologies Used

- PHP 8.1 or higher
- Symfony 7.1
- MySQL
- Composer
- OpenSSL

## Installation

### Prerequisites

- Ensure you have PHP 8.1 or higher installed.
- Ensure the sodium extension is activated in PHP.ini
- Install Composer globally on your machine.
- Set up a MySQL database for the project.

### Steps to Install

1. **Install Dependencies**

   Use Composer to install the required PHP packages.

   ```bash
   composer install
   ```

2. **Set Up Environment Variables**

   Update the database connection settings and JWT secret:

   ```bash
   APP_ENV=dev
   DATABASE_URL=mysql://username:password@127.0.0.1:3306/database_name
   JWT_PASSPHRASE=your_jwt_secret
   ```

3. **Create the Database**

   Create the database schema & migration by running the following command:

   ```bash
   php bin/console doctrine:database:create
   php bin/console make:migration
   php bin/console doctrine:migrations:migrate
   ```

4. **Generate JWT Keys**

   Generate the public & private keys for JWT:

   ```bash
   php bin/console lexik:jwt:generate-keypair
   ```

5. **Add JWT User**

   Execute the query to add a user and generate the token:

   ```sql
   INSERT INTO `user` (`id`, `username`, `roles`, `password`)
   VALUES (NULL, 'admin', '[]', '$2y$13$xrrPuP5vlIuinnxosfWwnu7SPtq3veWjm6vZZ1MxvtJkaXCQxcke2');
   ```
