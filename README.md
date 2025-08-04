# 🚀 Laramikrotik - Professional PPPoE Management System

**Laramikrotik** adalah sistem manajemen PPPoE yang modern dan profesional yang dibangun dengan Laravel dan terintegrasi dengan Mikrotik RouterOS. Sistem ini menyediakan solusi lengkap untuk pengelolaan billing, monitoring usage, administrasi hotspot, dan **monitoring sistem real-time** yang komprehensif.

## ✨ Fitur Utama

### 📊 **Dashboard & Analytics**
- **Real-time Statistics**: Dashboard dengan statistik penggunaan data real-time
- **Interactive Charts**: ApexCharts untuk visualisasi data usage trends
- **Usage Analytics**: Analisis mendalam penggunaan data harian dan bulanan
- **Top Users Tracking**: Monitoring pengguna dengan konsumsi data tertinggi

### � **System Health Monitoring** ⭐ *NEW*
- **Real-time System Monitor**: Monitoring CPU, memory, dan storage secara real-time
- **Interface Status**: Monitoring status 24+ interface dengan traffic data
- **Health Sensors**: Monitoring temperature dan sensor hardware
- **Network Statistics**: Firewall rules dan routing table monitoring
- **Responsive 3-Card Layout**: Tampilan yang optimal di semua device

### �👥 **Customer Management**
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
- **API Fallback System**: Sistem fallback untuk koneksi yang tidak stabil

### 📈 **Monitoring & Reporting**
- **Usage Logs**: Log penggunaan data yang detail
- **Session Tracking**: Monitoring sesi pengguna
- **System Health**: Monitoring comprehensive sistem MikroTik
- **Network Monitoring**: Interface, firewall, dan routing monitoring
- **Data Analytics**: Analisis data menggunakan chart interaktif
- **Export Reports**: Export laporan dalam berbagai format

## 🛠️ Tech Stack

- **Backend**: Laravel 12.x
- **Frontend**: Bootstrap 4 (SB Admin 2 Theme) with Font Awesome icons
- **Database**: MySQL/MariaDB
- **Charts**: ApexCharts.js for interactive visualizations
- **API**: Mikrotik RouterOS API with PHP RouterOS library
- **Authentication**: Laravel Sanctum
- **UI Components**: DataTables, Select2, SweetAlert2
- **Monitoring**: Real-time system health monitoring
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
![System Health](<img width="1901" height="951" alt="8" src="https://github.com/user-attachments/assets/3dd6b428-30d9-4b3a-b2c7-851d7078dad7" />
)

### Dashboard Analytics
![Dashboard](<img width="1905" height="951" alt="1" src="https://github.com/user-attachments/assets/041f0901-cb6c-4fdc-a09f-05b12e9cda1f" />
)

### Usage Statistics
![Statistics](<img width="1904" height="950" alt="6" src="https://github.com/user-attachments/assets/d7fdde93-6fa0-4eef-bfd9-e330037bd4e0" />)

### Customer Management
![Customers](<img width="1903" height="950" alt="3" src="https://github.com/user-attachments/assets/cca6aeff-33f8-4c7c-8327-5bbc519a0b46" />)
![Customers](<img width="1903" height="950" alt="2" src="https://github.com/user-attachments/assets/4887bed6-1864-43ca-979d-900eb420ec30" />
)

### Payment ⭐ *NEW*
![Payment](<img width="1904" height="950" alt="7" src="https://github.com/user-attachments/assets/b1dfc5f6-d81f-4e71-b835-ae31e215415d" />
)

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
- `customers` - Data pelanggan
- `ppp_profiles` - Profile PPPoE Mikrotik
- `ppp_secrets` - Secret PPPoE users
- `usage_logs` - Log penggunaan data
- `invoices` - Data tagihan
- `payments` - Data pembayaran
- `mikrotik_settings` - Konfigurasi Mikrotik

### System Monitoring ⭐ *NEW*
- **Real-time API Integration**: Direct connection ke MikroTik RouterOS API
- **Fallback System**: Realistic data generation untuk demo dan testing
- **Timeout Protection**: Enhanced connection handling untuk koneksi yang tidak stabil
- **JSON API Endpoints**: RESTful API untuk monitoring data

## 🤝 Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📝 Changelog

### v1.1.0 (2025-08-03) ⭐ *LATEST*
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
