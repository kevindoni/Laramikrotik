# 🚀 Laramikrotik - Professional PPPoE Management System

**Laramikrotik** adalah sistem manajemen PPPoE yang modern dan profesional yang dibangun dengan Laravel dan terintegrasi dengan Mikrotik RouterOS. Sistem ini menyediakan solusi lengkap untuk pengelolaan billing, monitoring usage, administrasi hotspot, dan **monitoring sistem real-time** yang komprehensif.

## ✨ Fitur Utama

### 📊 **Dashboard & Analytics**
- **Real-time Statistics**: Dashboard dengan statistik penggunaan data real-time
- **Interactive Charts**: ApexCharts untuk visualisasi data usage trends
- **Usage Analytics**: Analisis mendalam penggunaan data harian dan bulanan
- **Top Users Tracking**: Monitoring pengguna dengan konsumsi data tertinggi
- **Revenue Analytics**: Tracking revenue dan projected income real-time

### 🖥️ **System Health Monitoring** ⭐ *NEW*
- **Real-time System Monitor**: Monitoring CPU, memory, dan storage secara real-time
- **Interface Status**: Monitoring status 24+ interface dengan traffic data
- **Health Sensors**: Monitoring temperature dan sensor hardware
- **Network Statistics**: Firewall rules dan routing table monitoring
- **Responsive 3-Card Layout**: Tampilan yang optimal di semua device

### 👥 **Customer Management**
- **Customer Database**: Manajemen lengkap data pelanggan
- **Profile Management**: Pengaturan profil PPPoE dengan berbagai paket
- **User Authentication**: Sistem login terintegrasi dengan role-based access
- **Customer Portal**: Dashboard khusus untuk pelanggan

### 💰 **Billing & Payment System** ⭐ *ENHANCED*
- **Automated Invoice Generation**: Generate invoice otomatis berdasarkan billing cycles
- **Overdue Management**: Auto-block system untuk user yang telat bayar
- **Payment Verification**: Sistem verifikasi pembayaran dengan status tracking
- **Console Commands**: Automated billing dengan artisan commands
- **Billing Cycles**: Flexible billing cycles (daily, weekly, monthly)
- **Payment History**: Riwayat pembayaran lengkap dengan receipt generation

### 🌐 **Mikrotik Integration** ⭐ *ENHANCED*
- **Auto-Sync System**: Sinkronisasi otomatis PPP secrets dan profiles
- **Retry Logic**: Connection management dengan progressive timeout
- **SSL Support**: Koneksi aman dengan SSL/TLS
- **Connection Pooling**: Optimasi performa dengan connection reuse
- **Fallback System**: Demo data untuk testing dan development
- **Error Handling**: Comprehensive error categorization dan logging
- **Real-time Traffic Monitoring**: Ethernet LAN traffic monitoring dengan update 2 detik ⭐

### 📈 **Monitoring & Reporting**
- **Usage Logs**: Log penggunaan data yang detail
- **Session Tracking**: Monitoring sesi pengguna
- **System Health**: Monitoring comprehensive sistem MikroTik
- **Network Monitoring**: Interface, firewall, dan routing monitoring
- **Data Analytics**: Analisis data menggunakan chart interaktif
- **Export Reports**: Export laporan dalam berbagai format

### ⚡ **Real-time Monitoring** ⭐ *NEW*
- **2-Second Updates**: Ethernet LAN traffic monitoring dengan refresh 2 detik
- **Live Data Updates**: Update data tanpa reload halaman menggunakan AJAX
- **Connection Status**: Indikator status koneksi real-time
- **Error Handling**: Exponential backoff untuk handling error
- **Performance Optimization**: Caching dan connection pooling
- **Visual Feedback**: Efek visual saat update data
- **Pause/Resume**: Kontrol real-time monitoring

## 🛠️ Tech Stack

- **Backend**: Laravel 12.x
- **Frontend**: Bootstrap 4 (SB Admin 2 Theme) with Font Awesome icons
- **Database**: MySQL/MariaDB
- **Charts**: ApexCharts.js for interactive visualizations
- **API**: Mikrotik RouterOS API with PHP RouterOS library
- **Authentication**: Laravel Sanctum
- **UI Components**: DataTables, Select2, SweetAlert2
- **Monitoring**: Real-time system health monitoring
- **Performance**: Connection pooling, caching, chunking
- **Security**: SSL/TLS, CSRF protection, input validation
- **Responsive Design**: Mobile-first responsive layout

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
git clone https://github.com/kevindoni/Laramikrotik.git
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

### System Health Monitoring Dashboard ⭐ *NEW*
![System Health](https://github.com/user-attachments/assets/3dd6b428-30d9-4b3a-b2c7-851d7078dad7)

### Dashboard Analytics
![Dashboard](https://github.com/user-attachments/assets/041f0901-cb6c-4fdc-a09f-05b12e9cda1f)

### Usage Statistics
![Statistics](https://github.com/user-attachments/assets/d7fdde93-6fa0-4eef-bfd9-e330037bd4e0)

### Customer Management
![Customers](https://github.com/user-attachments/assets/cca6aeff-33f8-4c7c-8327-5bbc519a0b46)
![Customer Details](https://github.com/user-attachments/assets/4887bed6-1864-43ca-979d-900eb420ec30)

### Payment System ⭐ *NEW*
![Payment](https://github.com/user-attachments/assets/b1dfc5f6-d81f-4e71-b835-ae31e215415d)

## 🎯 Key Features

### Real-time System Monitoring ⭐ *NEW*
- **System Health Dashboard**: Comprehensive monitoring untuk CPU, memory, dan storage
- **Interface Monitoring**: Real-time monitoring 24+ network interfaces dengan traffic statistics
- **Health Sensors**: Temperature monitoring dan hardware health sensors
- **Network Statistics**: Firewall rules dan routing table dengan status real-time
- **Responsive UI**: 3-card layout yang optimal untuk desktop dan mobile

### Advanced Data Analytics
- Live usage tracking dengan ApexCharts
- Interactive dashboard dengan drill-down capabilities
- Real-time sync dengan Mikrotik RouterOS
- Network performance monitoring

### Advanced Billing System
- Flexible billing cycles (daily, weekly, monthly)
- Automated invoice generation
- Payment tracking dan reminder system

### Professional UI/UX
- Responsive design untuk semua device
- Modern SB Admin 2 theme dengan Font Awesome icons
- Interactive charts dan data tables
- Color-coded status indicators
- Enhanced visual hierarchy

## 🔐 Security Features

- Role-based access control (Admin, Customer)
- Secure API authentication
- CSRF protection
- Input validation dan sanitization

## 📊 Database Schema

### Core Tables
- `customers` - Data pelanggan dengan identity card types
- `ppp_profiles` - Profile PPPoE Mikrotik dengan billing cycles
- `ppp_secrets` - Secret PPPoE users dengan auto-sync
- `usage_logs` - Log penggunaan data real-time
- `invoices` - Data tagihan dengan automated generation
- `payments` - Data pembayaran dengan verification system
- `mikrotik_settings` - Konfigurasi Mikrotik dengan connection management
- `notifications` - Sistem notifikasi terpisah

### System Monitoring ⭐ *ENHANCED*
- **Real-time API Integration**: Direct connection ke MikroTik RouterOS API
- **Fallback System**: Realistic data generation untuk demo dan testing
- **Timeout Protection**: Enhanced connection handling untuk koneksi yang tidak stabil
- **JSON API Endpoints**: RESTful API untuk monitoring data
- **Connection Pooling**: Optimasi koneksi untuk performa tinggi
- **Error Categorization**: Kategorisasi error untuk debugging
- **Performance Metrics**: Logging metrics performa untuk monitoring

## 🤝 Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📝 Changelog

### v1.3.0 (2025-08-03) ⭐ *LATEST*
- ⚡ **NEW**: Real-time 2-second Ethernet LAN traffic monitoring
- ⚡ **NEW**: Live data updates dengan AJAX tanpa page reload
- ⚡ **NEW**: Connection status indicator dan visual feedback
- ⚡ **NEW**: Performance optimization dengan caching dan error handling
- ⚡ **NEW**: Pause/Resume control untuk real-time monitoring

### v1.2.0 (2025-08-03)
- ✨ **NEW**: Auto-sync system untuk PPP secrets dan profiles
- 🔄 **NEW**: Real-time system health monitoring dengan fallback system
- 💰 **NEW**: Automated billing system dengan console commands
- 🔧 **NEW**: Enhanced Mikrotik API integration dengan retry logic
- 📊 **NEW**: Comprehensive error handling dan logging system
- 🛡️ **ENHANCED**: Security improvements dengan role-based access
- ⚡ **OPTIMIZED**: Performance improvements dengan connection pooling
- 🎨 **IMPROVED**: UI/UX enhancements dengan responsive design

### v1.1.0 (2025-08-03)
- ✨ **NEW**: Comprehensive System Health Monitoring
- 📊 **NEW**: Real-time interface monitoring (24+ interfaces)
- 🌡️ **NEW**: Hardware health sensors dan temperature monitoring
- 🛡️ **NEW**: Firewall statistics dan routing table monitoring
- 🎨 **IMPROVED**: Enhanced UI/UX dengan 3-card responsive layout
- 🐛 **FIXED**: Syntax errors dan layout issues
- 🔧 **ENHANCED**: MikroTik API integration dengan fallback system
- 📱 **ENHANCED**: Mobile-responsive design improvements

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
