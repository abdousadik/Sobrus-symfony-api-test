# Blog Article API

## Overview

The Blog Article API is a Symfony-based RESTful API that allows users to manage blog articles. This API includes features for creating, updating, retrieving, and deleting articles, with built-in JWT (JSON Web Token) security for authentication.

## Features

- Create, read, update, and delete blog articles
- JWT authentication for secure API access
- Input validation and error handling
- Extracts the top three frequently occurring words from text for keyword management.
- Excluding banned words from content

## Technologies Used

- PHP 8.1 or higher
- Symfony 7.1
- MySQL
- Composer
- JWT
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

   Update the database connection settings and JWT secret in .env:

   ```bash
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

## Endpoints

### Authentication

- **Register**: `POST /signup`
  ```json
  {
    "username": "username",
    "password": "securePassword"
  }
  ```
- **Login**: `POST /login_check`
  ```json
  {
    "username": "username",
    "password": "securePassword"
  }
  ```

### Article Management

- **Create Article**: `POST /blog-articles`
- **Update Article**: `PATCH /blog-articles/{id}`
- **Delete Article**: `DELETE /blog-articles/{id}`
- **List Articles**: `GET /blog-articles`
- **View Article by ID**: `GET /blog-articles/{id}`

### Testing

You can test the API using the provided Postman collection, named "SOBRUS.postman_collection," located in the collection folder. This collection contains predefined requests for all API endpoints to facilitate testing.

### Contact

For any inquiries or issues, please reach out to fox.sadik@gmail.com.
