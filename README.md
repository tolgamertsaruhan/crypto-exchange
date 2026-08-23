# Crypto Exchange

<p align="center">
  <strong>A web based cryptocurrency exchange simulation built with PHP and MySQL.</strong>
</p>

<p align="center">
  Simulate cryptocurrency trading, manage portfolios, track transactions, and monitor historical market prices through a role based web application.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap">
  <img src="https://img.shields.io/badge/CoinGecko-API-8DC647?style=for-the-badge" alt="CoinGecko API">
</p>

---

## Overview

**Crypto Exchange Project** is a web based cryptocurrency trading simulation application designed to reproduce the core workflow of a cryptocurrency exchange.

The application allows users to browse cryptocurrency prices, view historical market data, buy and sell assets using a virtual balance, manage their portfolio, and review their transaction history.

The system also provides a separate administrative experience for managing users and accessing user related information.

The application uses **PHP** for server side logic, **MySQL** for persistent data storage, **Bootstrap** for the interface, and the **CoinGecko API** for cryptocurrency market data.

---

## Features

### User Features

* User registration and login
* Session based authentication
* Virtual account balance
* Cryptocurrency search
* Cryptocurrency price tracking
* Historical cryptocurrency price data
* Cryptocurrency details
* Buy cryptocurrency
* Sell cryptocurrency
* Portfolio management
* Average purchase price tracking
* Current portfolio valuation
* Profit and loss calculation
* Transaction history
* Pagination
* Profile management
* Password change

### Admin Features

* Separate administrator access
* User management
* View user profiles
* View user portfolios
* View user transaction history
* Delete users
* Monitor user related information

### Market Data

* CoinGecko API integration
* Support for a large cryptocurrency list
* Daily price storage
* Historical price records
* Automatic detection of missing daily prices
* Batched API requests
* API rate limit protection
* Local database caching of market prices

---

## Application Flow

### User Flow

```text
Register
   │
   ▼
Login
   │
   ▼
Browse Cryptocurrencies
   │
   ├──────────────► View Details
   │                    │
   │                    ▼
   │              View Price History
   │
   ▼
Buy Cryptocurrency
   │
   ▼
Portfolio
   │
   ├──────────────► View Holdings
   │
   ├──────────────► Sell Cryptocurrency
   │
   ▼
Transaction History
```

### Admin Flow

```text
Admin Login
    │
    ▼
Admin Dashboard
    │
    ▼
User Management
    │
    ├──────────────► View User
    │
    ├──────────────► View Portfolio
    │
    ├──────────────► View Transaction History
    │
    └──────────────► Delete User
```

---

## Technology Stack

| Technology        | Purpose                                   |
| ----------------- | ----------------------------------------- |
| **PHP**           | Backend and server side application logic |
| **MySQL**         | Persistent data storage                   |
| **HTML5**         | Application structure                     |
| **CSS3**          | Custom styling                            |
| **Bootstrap 5**   | Responsive UI components                  |
| **JavaScript**    | Client side interactions                  |
| **CoinGecko API** | Cryptocurrency market data                |
| **Git / GitHub**  | Version control                           |

---

## Preview

<p align="center">
  <img src="https://github.com/tolgamertsaruhan/crypto-exchange/blob/main/image-for-readme/crypto-exchange-project-gif.gif" alt="Crypto Exchange Preview">
</p>

---

## License

This project is licensed under the **MIT License**.

See the [LICENSE](LICENSE) file for more information.
