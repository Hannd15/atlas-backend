# ATLAS Auth Backend

## Project Overview

This project is the authentication and authorization module for the ATLAS system. It is a Laravel v12.0 application that provides a robust and scalable security layer for the ATLAS ecosystem.

**Main Technologies:**

*   **Backend:** Laravel 12, PHP 8.2+
*   **Authentication:** Laravel Sanctum (for API tokens and session management), Laravel Socialite (for OAuth2 with Google)
*   **Authorization:** Spatie Laravel Permission (for roles and permissions)
*   **API Documentation:** OpenAPI/Swagger
*   **Testing:** PHPUnit
*   **Code Style:** Laravel Pint

**Architecture:**

The project follows an extended Model-View-Controller (MVC) pattern with additional layers for services and repositories.

*   **HTTP Layer:** Controllers, Middleware, and Form Requests handle incoming requests.
*   **Service Layer:** Contains the business logic of the application.
*   **Repository Layer:** Abstracts the data access and persistence logic.
*   **Authentication & Authorization:** Laravel Sanctum, Spatie Laravel Permission, and Laravel Socialite are used for security.
*   **Models & Persistence:** Eloquent ORM is used for database interaction, with repositories abstracting the persistence layer.

## Building and Running

**Prerequisites:**

*   PHP 8.2 or higher
*   Composer 2.x
*   Node.js 18+ and npm
*   A database (MySQL, PostgreSQL, or SQLite)

**Installation and Setup:**

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/Hannd15/atlas-auth-backend.git
    cd atlas-auth-backend
    ```

2.  **Install PHP dependencies:**
    ```bash
    composer install
    ```

3.  **Install Node.js dependencies:**
    ```bash
    npm install
    ```

4.  **Configure environment variables:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

5.  **Configure the database and run migrations and seeders:**
    ```bash
    php artisan migrate --seed
    ```

6.  **Install Passport (if used):**
    ```bash
    php artisan passport:install
    ```

7.  **Compile assets:**
    ```bash
    npm run build
    ```

**Running the Application:**

*   **Start the development server:**
    ```bash
    php artisan serve
    ```

*   **Run the development script (server, queue, logs, and vite):**
    ```bash
    npm run dev
    ```

**Testing:**

*   **Run the test suite:**
    ```bash
    npm test
    ```

## Development Conventions

*   **Code Style:** The project uses Laravel Pint to enforce a consistent code style.
*   **API Documentation:** The API is documented using OpenAPI/Swagger. The documentation can be accessed at `http://localhost:8000/api/documentation`.
*   **Database Seeding:** The database is seeded with a large amount of sample data for users, roles, and permissions, which is useful for testing and development.
*   **Authentication:** The application uses Laravel Sanctum for API authentication.
*   **Authorization:** The application uses the Spatie Laravel Permission package for role-based access control.
