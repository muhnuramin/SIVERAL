# Production Deployment Guide - Vite Manifest Fix

## Masalah Yang Ditemui

Ketika deploy ke production, aplikasi mengalami error:

- **"Unable to locate file in Vite manifest: resources/js/pages/auth/Login.vue"**
- **Error 500** karena file tidak ditemukan di manifest

## Penyebab Masalah

1. **Environment Production**: App di-set ke `APP_ENV=production` yang menggunakan built assets dari manifest
2. **Dynamic Imports**: Vite mem-bundle halaman ke dalam chunks (`_pages-auth-XXXXX.js`) tanpa entry individual
3. **Laravel Vite Plugin**: Mencari file spesifik di manifest tetapi yang ada adalah bundled chunks

## Solusi Yang Diterapkan

### 1. Modifikasi Vite Configuration

**File**: `vite.config.ts`

Menambahkan explicit entries untuk halaman-halaman kritis:

```typescript
laravel({
    input: [
        'resources/js/app.ts',
        // Explicitly include critical pages for manifest entries
        'resources/js/pages/auth/Login.vue',
        'resources/js/pages/auth/Register.vue',
        'resources/js/pages/auth/ForgotPassword.vue',
        'resources/js/pages/auth/ResetPassword.vue',
        'resources/js/pages/auth/VerifyEmail.vue',
        'resources/js/pages/auth/ConfirmPassword.vue',
        'resources/js/pages/Dashboard.vue',
        'resources/js/pages/Satuan/Index.vue',
        // Add other critical pages as needed
    ],
    ssr: 'resources/js/ssr.ts',
    refresh: true,
}),
```

### 2. Rebuild Assets

```bash
npm run build
```

### 3. Clear Laravel Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Hasil Setelah Fix

### Manifest Entries (Sebelum)

```json
{
    "_pages-auth-BFT8NPHA.js": {
        "file": "assets/pages-auth-BFT8NPHA.js",
        "name": "pages-auth",
        "isDynamicEntry": true
        // ... no individual page entries
    }
}
```

### Manifest Entries (Sesudah)

```json
{
    "resources/js/pages/auth/Login.vue": {
        "file": "assets/Login-wjRpd_pi.js",
        "name": "Login",
        "src": "resources/js/pages/auth/Login.vue",
        "isEntry": true,
        "isDynamicEntry": true
        // ... proper individual entry
    },
    "resources/js/pages/Satuan/Index.vue": {
        "file": "assets/Index-BwgPrVlR.js",
        "name": "Index",
        "src": "resources/js/pages/Satuan/Index.vue",
        "isEntry": true
        // ... proper individual entry
    }
}
```

## Checklist untuk Production Deployment

### ✅ **Sebelum Deploy**

1. Pastikan semua halaman kritis ditambahkan ke `vite.config.ts` input array
2. Run `npm run build` untuk generate manifest yang benar
3. Test locally dengan `APP_ENV=production`

### ✅ **Saat Deploy**

1. Upload semua file termasuk folder `public/build/`
2. Set environment variables yang benar:
    ```env
    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://your-domain.com
    ```
3. Run Laravel commands:
    ```bash
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    ```

### ✅ **Setelah Deploy**

1. Test halaman auth (login, register, dll)
2. Test halaman utama (dashboard, satuan, dll)
3. Check browser console untuk error
4. Verify assets loading dengan benar

## Halaman Yang Sudah Tersedia di Production

### ✅ **Auth Pages**

- `/login` - Login page
- `/register` - Register page
- `/forgot-password` - Forgot password
- `/reset-password` - Reset password
- `/verify-email` - Email verification
- `/confirm-password` - Password confirmation

### ✅ **Main Pages**

- `/dashboard` - Dashboard
- `/satuan` - Satuan CRUD page

### ✅ **Dynamic Pages**

- Semua halaman lain akan di-load secara dynamic melalui chunks

## Troubleshooting

### Jika Masih Error "Unable to locate file in Vite manifest"

1. **Check manifest**: Pastikan file ada di `public/build/manifest.json`
2. **Rebuild**: Run `npm run build` lagi
3. **Clear cache**: Run `php artisan config:clear`
4. **Add to Vite config**: Tambahkan halaman yang bermasalah ke `input` array

### Jika Error 500

1. **Check logs**: `storage/logs/laravel.log`
2. **Enable debug**: Set `APP_DEBUG=true` sementara untuk melihat error detail
3. **File permissions**: Pastikan folder `storage/` dan `bootstrap/cache/` writable

### Jika Assets Tidak Load

1. **Check URL**: Pastikan `APP_URL` sesuai dengan domain
2. **Check files**: Pastikan folder `public/build/` terupload
3. **Check manifest**: Pastikan `manifest.json` ada dan valid

## Best Practices untuk Production

### 🔧 **Development vs Production**

- **Development**: Gunakan `npm run dev` dan `APP_ENV=local`
- **Production**: Gunakan `npm run build` dan `APP_ENV=production`

### 🔧 **Asset Management**

- Selalu run `npm run build` sebelum deploy
- Upload seluruh folder `public/build/`
- Jangan edit files di `public/build/` manual

### 🔧 **Performance**

- Enable asset caching di web server
- Use CDN untuk static assets jika perlu
- Monitor bundle sizes (current PDF bundle: ~2MB)

## File Structure untuk Production

```
public/
├── build/
│   ├── assets/
│   │   ├── Login-wjRpd_pi.js       # Login page
│   │   ├── Index-BwgPrVlR.js       # Satuan page
│   │   ├── Dashboard-B4LXdOhn.js   # Dashboard
│   │   ├── app-BiZ3TlRC.js         # Main app
│   │   ├── vue-CxnqfqPw.js         # Vue framework
│   │   ├── vendor-BkvOLkd0.js      # Vendor libs
│   │   ├── pdf-qWCD_lmy.js         # PDF library (large)
│   │   ├── app-DWv0JlSu.css        # Main CSS
│   │   └── ...                     # Other assets
│   └── manifest.json               # Asset mapping
├── index.php
└── ...
```

Sekarang aplikasi sudah siap untuk production dengan semua halaman kritis tersedia di manifest! 🚀
