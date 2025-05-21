# AJR-Inventory-Management-System
System Overview
The Inventory Management System (IMS) is a web-based application developed to help businesses, especially in retail or distribution, monitor and manage their inventory efficiently. The system is designed to be user-friendly, with a clean interface and straightforward navigation. The core functionalities include viewing product quantities, tracking out-of-stock items, identifying top-selling products, and offering real-time interaction through a built-in chat assistant. The system is ideal for small to medium-sized enterprises looking for an organized way to keep track of their stock.

Technology Stack
The IMS is built using a typical full-stack web development approach, combining frontend technologies for the user interface with backend logic and database connectivity:

Frontend:HTML
CSS
JavaScript
jQuery
Chart.js for data visualization
Material Design Icons
Boxicons
HTML5 QR Code Scanner
Backend:
PHP (Server-side language)
MySQL (Database)
PDO and MySQLi for database connections
External Services:
OpenAI API integration
Google Authentication

Core Features
1. Dashboard
Real-time statistics
Visual analytics
Quick overview of inventory status
2. Product Management
Add/Edit/Delete products
Track product details (ID, name, price, stock)
3. Inventory Control
Real-time stock tracking
Low stock alerts
Automatic quantity updates
4. Checkout System
Barcode scanning
Manual entry
Transaction history
5. Reporting
Sales reports
Inventory reports
Data visualization
6. Security
User authentication
Google login integration
7. AI Features
Chat assistant
Product information queries

System Installation/Setup Guide
1. Open your XAMPP Control Panel and start Apache and MySQL.
2. Extract the downloaded source code zip file.
3. Copy the extracted source code folder and paste it into XAMPP's htdocs directory.
4. Open PHPMyAdmin in your browser: (http://localhost/phpmyadmin)
5. Create a new database named ajr.
6. Import the provided SQL file (e.g., ajr.sql) into the ajr database using PHPMyAdmin.
7. Browse the Inventory Management System in your browser: (http://localhost/AJR)

