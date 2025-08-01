# 🚀 Laramikrotik - Professional PPPoE Management System

**Laramikrotik** adalah sistem manajemen PPPoE yang modern dan profesional yang dibangun dengan Laravel dan terintegrasi dengan Mikrotik RouterOS. Sistem ini menyediakan solusi lengkap untuk pengelolaan billing, monitoring usage, dan administrasi hotspot.

## ✨ Fitur Utama

### 📊 **Dashboard & Analytics**
- **Real-time Statistics**: Dashboard dengan statistik penggunaan data real-time
- **Interactive Charts**: ApexCharts untuk visualisasi data usage trends
- **Usage Analytics**: Analisis mendalam penggunaan data harian dan bulanan
- **Top Users Tracking**: Monitoring pengguna dengan konsumsi data tertinggi

### 👥 **Customer Management**
- **Customer Database**: Manajemen lengkap data pelanggan
- **Profile Management**: Pengaturan profil PPPoE dengan berbagai paket
- **User Authentication**: Sistem login terintegrasi dengan role-based access
- **Customer Portal**: Dashboard khusus untuk pelanggan

### 💰 **Billing & Payment System**
- **Invoice Generation**: Pembuatan tagihan otomatis
- **Payment Tracking**: Pencatatan dan monitoring pembayaran
- **Billing Cycles**: Dukungan berbagai siklus penagihan
- **Payment History**: Riwayat pembayaran lengkap

### 🌐 **Mikrotik Integration**
- **Real-time Sync**: Sinkronisasi otomatis dengan Mikrotik RouterOS
- **PPPoE Management**: Manajemen secret dan profile PPPoE
- **Usage Monitoring**: Monitoring penggunaan bandwidth real-time
- **Automatic Commands**: Eksekusi perintah Mikrotik otomatis

### 📈 **Monitoring & Reporting**
- **Usage Logs**: Log penggunaan data yang detail
- **Session Tracking**: Monitoring sesi pengguna
- **Data Analytics**: Analisis data menggunakan chart interaktif
- **Export Reports**: Export laporan dalam berbagai format

## 🛠️ Tech Stack

- **Backend**: Laravel 12.x
- **Frontend**: Bootstrap 4 (SB Admin 2 Theme)
- **Database**: MySQL/MariaDB
- **Charts**: ApexCharts.js
- **API**: Mikrotik RouterOS API
- **Authentication**: Laravel Sanctum
- **UI Components**: DataTables, Select2, SweetAlert2

## 📋 Requirements

- PHP 8.3+
- Laravel 12.x
- MySQL 8.0+ / MariaDB 10.3+
- Composer
- Node.js & NPM
- Mikrotik RouterOS 6.x+

## 🚀 Installation

### 1. Clone Repository
```bash
git clone https://github.com/kevindoni17/Laramikrotik.git
cd Laramikrotik
```

### 2. Install Dependencies
```bash
composer install
npm install && npm run dev
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Configuration
Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laramikrotik
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. Run Migrations & Seeders
```bash
php artisan migrate
php artisan db:seed
php artisan db:seed --class=UsageLogSeeder
```

### 6. Start Development Server
```bash
php artisan serve
```

## 🔧 Configuration

### Mikrotik API Setup
1. Enable API di Mikrotik RouterOS
2. Buat user dengan permission API
3. Configure di `/mikrotik-settings`

### Default Admin Account
- **Email**: admin@laramikrotik.com
- **Password**: password

## 📸 Screenshots

### Dashboard Analytics
![Dashboard](https://via.placeholder.com/800x400?text=Dashboard+Analytics)

### Usage Statistics
![Statistics](https://via.placeholder.com/800x400?text=Usage+Statistics+with+ApexCharts)

### Customer Management
![Customers](https://via.placeholder.com/800x400?text=Customer+Management)

## 🎯 Key Features

### Real-time Data Monitoring
- Live usage tracking dengan ApexCharts
- Interactive dashboard dengan drill-down capabilities
- Real-time sync dengan Mikrotik RouterOS

### Advanced Billing System
- Flexible billing cycles (daily, weekly, monthly)
- Automated invoice generation
- Payment tracking dan reminder system

### Professional UI/UX
- Responsive design untuk semua device
- Modern SB Admin 2 theme
- Interactive charts dan data tables

## 🔐 Security Features

- Role-based access control (Admin, Customer)
- Secure API authentication
- CSRF protection
- Input validation dan sanitization

## 📊 Database Schema

### Core Tables
- `customers` - Data pelanggan
- `ppp_profiles` - Profile PPPoE Mikrotik
- `ppp_secrets` - Secret PPPoE users
- `usage_logs` - Log penggunaan data
- `invoices` - Data tagihan
- `payments` - Data pembayaran
- `mikrotik_settings` - Konfigurasi Mikrotik

## 🤝 Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📝 Changelog

### v1.0.0 (2025-08-02)
- ✨ Initial release
- 📊 Dashboard dengan ApexCharts integration
- 👥 Complete customer management system
- 💰 Billing dan payment system
- 🌐 Mikrotik RouterOS integration
- 📈 Usage analytics dan reporting

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

## 👨‍💻 Developer

**Kevin Doni**
- GitHub: [@kevindoni](https://github.com/kevindoni)
- Email: kevin@laramikrotik.com

## 🙏 Acknowledgments

- [Laravel Framework](https://laravel.com/)
- [SB Admin 2 Theme](https://startbootstrap.com/theme/sb-admin-2)
- [ApexCharts.js](https://apexcharts.com/)
- [Mikrotik RouterOS](https://mikrotik.com/)

---

**⭐ Star this repository if you find it helpful!**

Built with ❤️ using Laravel & ApexCharts
