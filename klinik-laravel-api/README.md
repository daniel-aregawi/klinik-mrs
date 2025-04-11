## Overview
The **Klinik Laravel API** is a backend solution designed to streamline clinic operations. Developed with **Laravel 11**, it offers a RESTful architecture to manage essential tasks such as patient appointments, doctor schedules, and patient records. The API is scalable and easy to integrate with frontend applications.

---

## Features
- 🩺 **Appointment Management**: Effortlessly manage doctor-patient appointments with options to create, update, and track them.
- 🗂️ **Patient Records**: Securely manage patient information, including storing, updating, and retrieving details.
- 🕒 **Doctor Schedules**: Dynamically manage doctor availability to avoid scheduling conflicts.
- 🔐 **Authentication**: Secure login and registration with Laravel Sanctum using token-based authentication.
- 📡 **RESTful API**: Fully documented endpoints for easy integration with any frontend.
- 🔑 **Role-Based Access Control**: Define permissions for admins, doctors, nurses, pharmacists, and patients.
- 🚀 **Scalable Architecture**: Optimized for handling large-scale clinic operations with room for future growth.
- 🏢 **Admin Dashboard**: Centralized platform to manage appointments, roles, schedules, and more.
- 👨‍⚕️ **Doctors Dashboard**: Manage appointments, check doctor schedules, and access patient records.
- 👩‍⚕️ **Nurses Dashboard**: Manage patient data, assist with scheduling, and track medications.
- 💊 **Pharmacists Dashboard**: Control pharmaceutical records, medication orders, and inventory.
- 🧑‍⚕️ **Patients Dashboard**: View health records, upcoming appointments, and medical history.
- and more...

---

## Tech Stack
- **Language**: PHP
- **Framework**: Laravel 11
- **Database**: MySQL
- **Authentication**: Laravel Sanctum
- **API**: RESTful
- **Other**: Composer, Node.js (optional for frontend build with Laravel Mix)

---

## Requirements
- **PHP**: >= 8.3
- **Composer**: Latest version
- **MySQL**: >= 5.7
- **Node.js**: >= 16.x (for frontend assets with Laravel Mix)
- **Git**: Latest version

composer install


npm install


cp .env.example .env


php artisan key:generate


DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=klinik_database
DB_USERNAME=root
DB_PASSWORD=


php artisan migrate


php artisan db:seed


http://localhost:8000/api/documentation

