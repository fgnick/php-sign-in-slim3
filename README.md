## 📘 php-sign-in-slim3

This is a sample project using the **Slim 3** framework to build a sign-in page with **JWT (JSON Web Token)** authentication.  
It is lightweight, minimal, and intended for educational or prototyping use.

It can be deployed on any typical **Apache2 + PHP + MySQL** development stack.

### 🛠️ System Requirements

To run this project properly, make sure your environment meets the following version requirements:

- **Apache**: 2.4.x
- **PHP**: 7.x (tested with PHP 7.4)
- **MySQL**: 8.0.x
- **Composer**: Required for installing Slim version 3 and dependencies

> 📌 Note: Other versions may work but are not officially tested.

### 🚀 Getting Started

### Prerequisites

To understand this project, it's highly recommended to first familiarize yourself with the **Slim 3 framework**. You can find all the necessary documentation and information at: [https://www.slimframework.com/docs/v3/](https://www.slimframework.com/docs/v3/)

---

### Vendor Directory Notes

The `/vendor` directory contains dependencies managed by Composer. While there might be minor personal modifications to some of these files, they should not affect your ability to directly download and use the original, open-source code from their respective authors.

---

### Frontend Integration

This project primarily features a dedicated space for **Vue.js** integration. The PHP counterpart that interacts with the Vue.js frontend is located at `/src/AppVue.php`.

Please place all frontend code (written by your frontend engineers) into the `/public` directory.

---

### Web Server Configuration

To ensure the project runs correctly, your web server's document root or corresponding project path should be set to the `/public` directory.

For example:

```apacheconf
<VirtualHost _default_:80>
  ServerName localhost
  ServerAlias localhost
  DocumentRoot "<span class="math-inline">{INSTALL_DIR}/www/php-sign-in-slim3/public"
  <Directory "</span>{INSTALL_DIR}/www/php-sign-in-slim3/public/">
    Options -Indexes -Includes +FollowSymLinks
    AllowOverride All
    Require all granted
  </Directory>

  ErrorLog "<span class="math-inline">\{INSTALL_DIR\}/logs/php-sign-in-slim3\-error\.log"
  CustomLog "</span>{INSTALL_DIR}/logs/php-sign-in-slim3-access.log" combined
</VirtualHost>
