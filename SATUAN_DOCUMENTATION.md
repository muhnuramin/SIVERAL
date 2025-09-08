# Halaman Satuan - CRUD Features

## Overview

Halaman Satuan adalah fitur untuk mengelola data satuan (unit of measurement) yang digunakan dalam sistem anggaran. Fitur ini mencakup operasi CRUD lengkap (Create, Read, Update, Delete).

## Features

### 1. **Tampilan List Satuan**

- Menampilkan data satuan dalam bentuk tabel dengan pagination
- Kolom yang ditampilkan: Nama Satuan, Tanggal Dibuat, Tanggal Diperbarui, Actions
- Fitur pencarian real-time berdasarkan nama satuan
- Responsif untuk berbagai ukuran layar

### 2. **Tambah Satuan (Create)**

- Modal form untuk menambah satuan baru
- Validasi:
    - Nama satuan wajib diisi
    - Maksimal 255 karakter
    - Tidak boleh duplikat
    - Otomatis dikonversi ke lowercase
- Notifikasi sukses/error setelah operasi

### 3. **Edit Satuan (Update)**

- Modal form untuk mengedit satuan yang sudah ada
- Validasi sama dengan create, kecuali unique validation mengabaikan record yang sedang diedit
- Pre-fill form dengan data yang sudah ada
- Notifikasi sukses/error setelah operasi

### 4. **Hapus Satuan (Delete)**

- Modal konfirmasi sebelum menghapus
- Proteksi: tidak bisa menghapus satuan yang sedang digunakan di SSH items
- Notifikasi sukses/error setelah operasi

### 5. **API Endpoint**

- `/api/satuans` - untuk mengambil data satuan sebagai dropdown/select options
- Berguna untuk integrasi dengan form-form lain yang membutuhkan pilihan satuan

## File Structure

### Backend

```
app/
├── Http/
│   ├── Controllers/
│   │   └── SatuanController.php    # CRUD operations
│   └── Requests/
│       └── SatuanRequest.php       # Validation rules
└── Models/
    └── Satuan.php                  # Model untuk tabel satuans
```

### Frontend

```
resources/js/
├── pages/
│   └── Satuan/
│       └── Index.vue               # Halaman utama satuan
└── components/
    └── AppSidebar.vue              # Updated dengan menu Satuan
```

### Routes

```php
// Web routes
GET    /satuan                      # Halaman index
POST   /satuan                      # Store new satuan
PUT    /satuan/{satuan}             # Update satuan
DELETE /satuan/{satuan}             # Delete satuan

// API routes
GET    /api/satuans                 # Get all satuans for dropdown
```

## Database

### Table: `satuans`

- `id` (primary key)
- `nama` (string, unique, lowercase)
- `created_at`
- `updated_at`

### Seeder

Data awal 32 satuan sudah tersedia melalui `SatuanSeeder`:

- ohm, ampere, watt, volt, kilowatt, dsb.

## Access & Navigation

- **Menu**: Dashboard → Master → Satuan
- **Breadcrumb**: Dashboard → Satuan
- **Authorization**: Middleware `auth` dan `verified`

## Technical Features

- **TypeScript**: Full type safety di frontend
- **Inertia.js**: Server-side rendering dengan SPA experience
- **Form Validation**: Client-side dan server-side validation
- **Error Handling**: Comprehensive error messages
- **Loading States**: Proper loading indicators
- **Responsive Design**: Mobile-friendly interface
- **Dark Mode**: Support untuk dark/light theme

## Usage Examples

### Menambah Satuan Baru

1. Klik tombol "Tambah Satuan"
2. Isi nama satuan (contoh: "kilogram")
3. Klik "Simpan"
4. Sistem akan validasi dan menyimpan data

### Menggunakan API

```javascript
// Fetch satuans for dropdown
fetch('/api/satuans')
    .then((response) => response.json())
    .then((satuans) => {
        // Use satuans data for select options
    });
```

## Security & Validation

- Sanitasi input (lowercase, trim)
- Unique constraint di database level
- CSRF protection
- Authorized access only
- Soft dependency checking (mencegah hapus satuan yang sedang digunakan)
