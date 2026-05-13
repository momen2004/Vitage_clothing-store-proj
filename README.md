# Vitage — Vintage Clothing E-Commerce

A full-stack PHP + MySQL vintage clothing store built as a university project.
HTML5, CSS3, vanilla JavaScript on the front; PHP (PDO) + MySQL on the back; XAMPP
for the local stack.

The aesthetic leans into vintage: earth tones, retro typography (Playfair Display,
Cormorant Garamond, Special Elite), sepia photo treatment, hand-drawn dashed
borders, and a dark/sepia theme toggle.

## Features

### Storefront
- Featured products + “just arrived” on the homepage
- Categories: Pants, Shirts, T-Shirts, Shoes, Belts, Caps, Accessories
- Search, sort (newest/price/name) and price-range filtering
- Pagination
- Product detail pages with image gallery, size picker, related items, view counter
- AJAX add-to-cart with toast notifications and live cart-badge updates
- Shopping cart persisted in MySQL per user (survives logout)
- Checkout that decrements stock and creates an order atomically
- User account with order history
- Sepia / dark mode toggle (persisted via cookie)

### Authentication
- Register / login / logout with `password_hash()` (bcrypt) + `password_verify()`
- Session regeneration on login
- Role-based access (`user` vs `admin`) gating
- CSRF tokens on every state-changing form

### Admin dashboard
- KPI cards: revenue, orders, products, customers, low-stock, pending
- Product CRUD with image upload (3 slots), sizes, stock, featured flag
- Category CRUD (add, rename, delete)
- Orders list + detail view + status workflow (pending → paid → shipped → delivered / cancelled)
- User management (promote/demote, delete)
- Analytics page with a 14-day revenue bar chart, top sellers, category breakdown,
  and an activity log

### Security
- PDO prepared statements (no string-concatenated SQL)
- Bcrypt password hashing
- CSRF tokens (`csrf_token` / `csrf_verify`)
- `htmlspecialchars` output escaping via the `e()` helper
- `.htaccess` denies direct access to `config/`, `includes/`, and `.sql` / `.md` files
- File-upload extension allow-list

## Setup (XAMPP)

1. Copy the project folder into `xampp/htdocs/`, e.g.
   `xampp/htdocs/Vitage_clothing-store-proj/`.
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. (Optional) edit `config/db.php` if your MySQL has a non-default user/password.
4. Visit <http://localhost/Vitage_clothing-store-proj/install.php> once — this
   creates the `vitage_store` database, all tables, seeds categories and products,
   and creates the demo accounts.
5. **Delete `install.php`** afterwards.

### Demo accounts (after install)

| Role  | Email                | Password   |
| ----- | -------------------- | ---------- |
| Admin | admin@vitage.local   | `admin123` |
| User  | jane@vitage.local    | `user123`  |

## Project layout

```
Vitage_clothing-store-proj/
├── index.php              Homepage (featured + new arrivals)
├── shop.php               Product listing w/ search + filters
├── product.php            Product detail page
├── cart.php               Shopping cart (update / remove / clear)
├── checkout.php           Place an order
├── account.php            User profile + order history
├── login.php register.php logout.php
├── install.php            One-time installer
├── api/
│   └── add_to_cart.php    AJAX endpoint (JSON)
├── admin/
│   ├── index.php          Dashboard with KPIs
│   ├── products.php       Product list + delete
│   ├── product_form.php   Create / edit product (with image upload)
│   ├── categories.php     Category CRUD
│   ├── orders.php         Order list + detail + status
│   ├── users.php          User management
│   ├── analytics.php      Charts, top sellers, activity log
│   └── includes/          Admin layout fragments
├── includes/
│   ├── functions.php      Helpers (auth, CSRF, cart, escaping)
│   ├── header.php / footer.php
│   └── _product_card.php  Shared product-card partial
├── config/
│   └── db.php             PDO connection
├── database/
│   └── schema.sql         Full schema + seed (run via install.php)
├── assets/
│   ├── css/ style.css     Storefront stylesheet
│   ├── css/ admin.css     Admin stylesheet
│   ├── js/ main.js        AJAX cart, theme toggle, gallery
│   └── images/            SVG product placeholders
└── uploads/products/      Admin-uploaded product images
```

## Database tables

`users`, `categories`, `products`, `cart`, `cart_items`, `orders`, `order_items`,
`activity_logs` — all related by foreign keys, with `ON DELETE CASCADE` where it
makes sense. See `database/schema.sql` for the exact DDL.

## Notes

- This is a demo university project. Orders are marked “paid” immediately — no
  real payment processing is wired up.
- SVG placeholders ship with the seed data so the storefront looks complete
  before you upload your own product photos.
- The codebase is intentionally framework-free so the underlying patterns
  (sessions, PDO, prepared statements, CSRF, role-based access) are easy to read
  and explain.
