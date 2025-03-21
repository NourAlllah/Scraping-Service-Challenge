# **E-Commerce Product Scraper and Viewer**

## 1. Backend (Laravel - PHP)
- Set up a Laravel project with a MySQL database.
- Create a `Product` model with fields: `id`, `title`, `price`, `image_url`, and `created_at`.
- Implement a scraping service that:
  - Fetches product details from an eCommerce product page (e.g., Amazon, Jumia).
  - Uses **Guzzle HTTP client** to make requests.
  - Rotates between different **user-agent headers** to mimic proxy rotation.
  - Stores the scraped product data in the MySQL database.
- Create an API endpoint (`/api/products`) to return the stored products in **JSON format**.
- Implement a **Golang microservice** that handles proxy management (e.g., rotating proxies dynamically).

---

## 2. Frontend (Next.js - React)
- Build a simple **Next.js** page (`/products`) that:
  - Fetches the scraped product data from the **Laravel API** (`/api/products`).
  - Displays the products in a **responsive grid layout**, showing the `title`, `price`, and `image`.
  - **Refreshes the data every 30 seconds**.

---

## How to Run the Project

### Backend (Laravel)
1. Clone the repository:
   ```sh
   git clone https://github.com/yourusername/repository-name.git
   cd repository-name
