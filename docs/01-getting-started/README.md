# 💰 ExpenseSettle - Split Expenses with Friends!

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.0-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![Chart.js](https://img.shields.io/badge/Chart.js-4.0-FF6384?style=for-the-badge&logo=chart.js&logoColor=white)

**A beautiful, fun, and interactive expense sharing app for friends and groups!**

[Features](#-features) • [Installation](#-installation) • [Demo](#-demo) • [Tech Stack](#-tech-stack)

</div>

---

## 🎯 Overview

ExpenseSettle is a modern, colorful expense tracking and splitting application designed for friends, roommates, and travel groups. Say goodbye to boring spreadsheets and hello to a fun, emoji-filled experience! 🎉

### Why ExpenseSettle?

- 🎨 **Beautiful UI** - Gradients, emojis, and modern design
- 📱 **Mobile First** - Works perfectly on all devices  
- 🚀 **Fast & Easy** - Track expenses in seconds
- 👥 **Group Friendly** - Perfect for trips, roommates, and events
- 💸 **Smart Splitting** - Equal, custom, or percentage splits
- 📊 **Visual Analytics** - Charts and graphs for insights
- �� **Fun Experience** - Confetti celebrations and friendly language

---

## ✨ Features

### 🏠 Dashboard
- **Real-time Balance Overview** - See what you owe and what others owe you
- **Collapsible Sections** - Clean, organized view
- **Quick Stats** - Groups, pending payments, recent activity
- **Interactive Charts** - Donut and bar charts with Chart.js
- **Color-coded Cards** - Red (owe), Green (paid), Cyan (owed)

### 👥 Group Management
- **Create Groups** - With custom icons (🏖️ 🏔️ 🍷 🏠)
- **Add/Remove Members** - Easy member management
- **Group Dashboard** - Balances, settlements, analytics
- **Member Permissions** - Admin and member roles

### 💸 Expense Tracking
- **Multiple Split Types** - Equal, custom, percentage
- **Quick Add** - Fast expense creation
- **Comments** - Discuss expenses
- **Attachments** - Upload receipts

### 💰 Payment Management
- **Mark as Paid** - With notes and receipt upload
- **Payment History** - Track all payments
- **Smart Reminders** - Remind friends

### 📊 Analytics
- **Balance Charts** - Visual finances
- **Group Spending** - Track by group
- **Member Balances** - Who owes what

---

## �� Installation

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL/PostgreSQL

### Quick Start

\`\`\`bash
# Clone repository
git clone https://github.com/arunenoah/XpenseSettle.git
cd XpenseSettle

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env then run:
php artisan migrate:fresh --seed

# Build assets
npm run build

# Start server
php artisan serve
\`\`\`

Visit `http://localhost:8000`

**Login**: `arun@example.com` / `password`

---

## 🎮 Demo

### Test Accounts

| Email | Password | Groups |
|-------|----------|--------|
| arun@example.com | password | 4 groups |
| karthick@example.com | password | Goa admin |
| dhana@example.com | password | Ooty admin |

### Sample Data Included

- **6 users** - Ready to test
- **5 groups** - Roommates, Goa, Ooty, Lunch, Hunter Valley
- **19 expenses** - Hotels, food, groceries, spa
- **Multiple payments** - Some paid, some pending

---

## 🛠️ Tech Stack

- **Laravel 11** - PHP Framework
- **Tailwind CSS 3.0** - Styling
- **Chart.js 4.0** - Charts
- **Alpine.js** - Interactions
- **MySQL** - Database
- **Vite** - Asset bundling

---

## 📁 Project Structure

\`\`\`
expenseSettle/
├── app/
│   ├── Http/Controllers/
│   │   ├── DashboardController.php
│   │   ├── GroupController.php
│   │   ├── ExpenseController.php
│   │   └── PaymentController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Group.php
│   │   ├── Expense.php
│   │   └── Payment.php
│   └── Services/
│       ├── PaymentService.php
│       └── GroupService.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── views/
│   │   ├── dashboard.blade.php
│   │   ├── groups/
│   │   └── expenses/
│   └── css/
└── routes/
    └── web.php
\`\`\`

---

## 🎨 Design Features

### Color Coding
- 🔴 **Red** - You owe
- 🟢 **Green** - Paid
- 🔵 **Cyan** - They owe you
- 🟣 **Purple** - Active states

### UI Elements
- Gradients & emojis
- Collapsible sections
- Toast notifications
- Confetti animations
- Loading skeletons
- Responsive design

---

## 🔮 Roadmap

### ✅ Phase 1 (Done)
- User authentication
- Group management
- Expense tracking
- Payment management
- Dashboard with charts
- Mobile responsive

### 🚧 Phase 2 (In Progress)
- Dark mode
- Email notifications
- Export to CSV/PDF
- Multi-currency

### 📋 Phase 3 (Planned)
- Mobile app
- Real-time updates
- Payment integrations
- Advanced analytics

---

## 🤝 Contributing

Contributions welcome! Please:

1. Fork the repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Open Pull Request

---

## 📝 License

MIT License - Open source

---

## 👨‍💻 Author

**Arun Kumar**

- GitHub: [@arunenoah](https://github.com/arunenoah)
- Repository: [XpenseSettle](https://github.com/arunenoah/XpenseSettle)

---

## 🙏 Acknowledgments

- Laravel - PHP framework
- Tailwind CSS - Styling
- Chart.js - Charts
- Splitwise - Inspiration
- **Claude Code** - AI assistant for app development, mobile app setup, and documentation

---

<div align="center">

**Made with ❤️ and lots of ☕**

⭐ Star this repo if you find it helpful!

[View Demo](http://localhost:8000) • [Report Bug](https://github.com/arunenoah/XpenseSettle/issues) • [Request Feature](https://github.com/arunenoah/XpenseSettle/issues)

</div>
