# Laravel Real-Time Analytics

A Laravel-based test project demonstrating real-time order creation and analytics using **Pusher** for event broadcasting.

---

## 🚀 Features
- Create Order API (`POST /api/orders`)
- Get Active Orders API (`GET /api/orders/active`)
- Real-time updates via **Pusher** (`OrderCreated` event)
- RESTful structure with Laravel 9 and PHP 8
- Simple real-time dashboard to visualize new orders

---

## ⚙️ Installation

### 1. Clone the repository
```bash
git clone https://github.com/yourusername/laravel-realtime-analytics.git
cd laravel-realtime-analytics
2. Install dependencies
bash
Copy code
composer install
3. Environment setup
Copy .env.example and update database + Pusher credentials:

bash
Copy code
cp .env.example .env
php artisan key:generate
4. Run migrations
bash
Copy code
php artisan migrate
5. Serve the app
bash
Copy code
php artisan serve
The app will be available at:
👉 http://127.0.0.1:8000

📡 API Endpoints
Method	Endpoint	Description
POST	/api/orders	Create a new order
GET	/api/orders/active	Fetch active orders

🖥️ Real-Time Dashboard
A simple Laravel Blade view that displays new orders instantly when they’re created.

Route:

arduino
Copy code
http://127.0.0.1:8000/dashboard
How it works:

Listens on the orders channel for the OrderCreated event through Pusher.

When a new order is placed (via POST /api/orders), it appears automatically on the dashboard without refreshing.

Uses the Pusher JavaScript library to subscribe and update the page in real-time.

🧩 Technologies
Laravel 9

MySQL

Pusher (Realtime Events)

PHP 8.0

JavaScript (Pusher JS)

👨‍💻 Author
Syed Nabeel Junaid
Laravel & Full Stack Developer
