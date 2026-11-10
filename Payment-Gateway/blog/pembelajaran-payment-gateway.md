---
title: "Belajar Payment Gateway dengan Laravel dan Midtrans"
slug: pembelajaran-payment-gateway-laravel-midtrans
excerpt: "Memahami alur pembayaran sederhana dengan Laravel, Midtrans Snap, session, dan pengecekan status transaksi."
category: Teknologi
published_at: 2026-11-10 15:10
sort_order: 10
meta_description: "Pembelajaran integrasi payment gateway Midtrans Snap di Laravel: membuat transaksi, redirect pembayaran, cek status, dan menangani error untuk pemula."
is_published: true
---

# Belajar Payment Gateway dengan Laravel dan Midtrans

Payment gateway membantu aplikasi menerima pembayaran melalui layanan pihak ketiga. Pada contoh ini, Laravel membuat transaksi melalui **Midtrans Snap**, mengarahkan pengguna ke halaman pembayaran, lalu memeriksa status transaksi.

Contoh yang dipelajari adalah pembayaran tiket demo sebesar **Rp10.000**. Implementasinya memakai controller, route, Blade, dan session Laravel.

## Gambaran alur pembayaran

1. Pengguna membuka halaman `/payment`.
2. Pengguna menekan tombol **Bayar Rp10.000**.
3. Laravel membuat kode pesanan unik dan meminta Midtrans membuat transaksi Snap.
4. Laravel menyimpan data transaksi sementara di session dan mengarahkan pengguna ke halaman pembayaran Midtrans.
5. Setelah mencoba membayar, pengguna kembali ke aplikasi dan menekan **Cek Status**.
6. Laravel meminta status transaksi ke Midtrans, memverifikasi kode pesanan dan nominal, lalu memperbarui status menjadi `paid` jika transaksi berhasil.

Secara sederhana:

```text
Browser → Laravel → Midtrans Snap
                    ↓
Browser ← halaman pembayaran
Browser → Laravel → cek status ke Midtrans
```

## 1. Konfigurasi kredensial

Server key Midtrans disimpan sebagai environment variable, bukan ditulis langsung di source code:

```dotenv
KEY_MIDTRANS_SERVER_KEY=isi_server_key_midtrans
```

`config/services.php` membaca nilai tersebut:

```php
'midtrans' => [
    'server_key' => env('KEY_MIDTRANS_SERVER_KEY'),
],
```

Controller mengambilnya menggunakan nama konfigurasi Laravel yang sesuai:

```php
Config::$serverKey = config('services.midtrans.server_key');
Config::$isProduction = false;
```

Pada contoh ini, `isProduction` bernilai `false`, sehingga integrasi ditujukan untuk lingkungan sandbox. Jangan memasukkan server key sungguhan ke Git atau menampilkannya ke browser. Jika konfigurasi di-cache, jalankan `php artisan config:clear` setelah mengganti environment variable.

## 2. Route untuk halaman dan aksi pembayaran

Route aplikasi memisahkan tiga aksi:

| Method | URL | Kegunaan |
|---|---|---|
| `GET` | `/payment` | Menampilkan halaman pembayaran |
| `POST` | `/payment` | Membuat atau membuka kembali transaksi |
| `POST` | `/payment/check` | Memeriksa status transaksi |

Aksi yang mengubah atau memeriksa transaksi memakai `POST`. Form Blade menyertakan `@csrf` agar permintaan dilindungi CSRF Laravel.

## 3. Membuat transaksi Snap

Controller membuat ID pesanan dengan UUID agar tidak mudah bertabrakan. Nilai `gross_amount` pada contoh adalah `10000`, yaitu nominal Rp10.000 dalam rupiah:

```php
$orderCode = 'DEMO-' . Str::uuid();
$amount = 10000;

$payment = Snap::createTransaction([
    'transaction_details' => [
        'order_id' => $orderCode,
        'gross_amount' => $amount,
    ],
]);
```

Jika transaksi berhasil dibuat, respons Midtrans menyertakan `redirect_url`. Browser diarahkan ke URL tersebut dengan `redirect()->away(...)` karena alamat tujuan berada di luar aplikasi.

Sebelum redirect, contoh menyimpan data berikut di session:

- `order_code`: ID pesanan yang dikirim ke Midtrans.
- `amount`: nominal transaksi.
- `status`: status internal, awalnya `pending`.
- `payment_url`: URL untuk membuka kembali halaman pembayaran.

Jika transaksi pending sudah tersimpan di session, tombol bayar membuka kembali URL pembayaran tersebut, bukan membuat transaksi baru. Jika statusnya sudah `paid`, pengguna diarahkan kembali ke halaman pembayaran.

## 4. Menampilkan status pada halaman

Blade menampilkan kode dan status pesanan dari session. Tombol **Bayar Rp10.000** hanya tampil jika belum ada pesanan atau statusnya belum `paid`. Tombol **Cek Status** tersedia ketika data pesanan ada.

Data session untuk satu transaksi demo membuat contoh ini mudah dipahami, tetapi bukan penyimpanan transaksi yang andal untuk aplikasi nyata. Session bisa hilang atau berbeda antarperangkat; transaksi sebenarnya sebaiknya disimpan di database.

## 5. Memeriksa status transaksi

Saat menerima permintaan cek status, controller:

1. Memastikan pesanan tersedia di session.
2. Meminta status Midtrans menggunakan `order_code`.
3. Memastikan `order_id` dari Midtrans sama dengan pesanan lokal.
4. Membandingkan nominal transaksi.
5. Menandai pesanan `paid` hanya jika status Midtrans adalah `settlement` atau `capture`.
6. Memberi pesan bahwa pembayaran belum berhasil untuk status lainnya.

Pengecekan ID dan nominal penting agar hasil yang dikembalikan gateway benar-benar cocok dengan pesanan yang sedang diperiksa. Nilai nominal dari respons gateway dapat berbentuk string desimal, sehingga contoh membandingkan keduanya setelah dikonversi ke `float`.

Untuk sistem produksi, perhitungan uang sebaiknya memakai representasi integer dalam satuan terkecil yang disepakati atau pendekatan decimal yang aman, bukan operasi floating point.

## 6. Menangani error gateway

Pemanggilan Midtrans dapat gagal karena kredensial tidak sesuai, koneksi bermasalah, atau respons layanan tidak valid. Controller menangkap exception, mencatatnya dengan `report($e)`, lalu mengembalikan pesan error ke halaman. Blade menampilkan pesan tersebut agar pengguna tahu bahwa permintaan pembayaran gagal.

Jangan menampilkan detail exception atau informasi sensitif kepada pengguna di aplikasi produksi. Tampilkan pesan umum, sementara rincian teknis tetap berada di log.

## Kesalahan yang perlu diwaspadai

### Kesalahan sintaks PHP

Akses properti object harus menggunakan bentuk seperti `$result->order_id`. Bentuk `$result->['order_id']` tidak valid dan menyebabkan parse error. Akibatnya, bahkan halaman pembayaran bisa menghasilkan HTTP 500 sebelum tombol sempat digunakan.

### Nama konfigurasi tidak sesuai

Jika konfigurasi didefinisikan di `services.php`, aksesnya harus menggunakan prefix `services`, contohnya:

```php
config('services.midtrans.server_key')
```

Salah mengetik menjadi `service.midtrans.server_key` membuat nilai server key tidak terbaca.

### Nama field pesanan tidak konsisten

Data transaksi pada session menggunakan `order_code`. Saat mengecek status, menampilkan data, dan memanggil Midtrans, gunakan nama field yang sama. Ketidaksesuaian seperti membaca `code` alih-alih `order_code` dapat menyebabkan ID kosong atau halaman gagal saat dirender.

### Route cek status tidak cocok dengan form

Jika form mengirim `POST`, route harus menerima `POST` juga. Method yang tidak cocok dapat menghasilkan HTTP 405 (Method Not Allowed).

## Batasan contoh dan langkah menuju produksi

Contoh ini ditujukan untuk belajar alur dasar. Sebelum digunakan untuk transaksi sungguhan:

- Simpan order dan status pembayaran di database, bukan hanya session.
- Verifikasi pembayaran melalui notifikasi/webhook Midtrans yang tervalidasi; jangan mengandalkan tombol cek status dari browser sebagai satu-satunya sumber kebenaran.
- Pastikan pemrosesan notifikasi idempotent agar notifikasi berulang tidak menggandakan pemenuhan pesanan.
- Gunakan validasi signature dan cocokkan order, nominal, mata uang, serta status dari gateway.
- Pisahkan kredensial sandbox dan produksi melalui environment yang berbeda.
- Jangan menandai transaksi berhasil hanya dari redirect kembali ke website; status pembayaran harus diverifikasi ke Midtrans.
- Catat kegagalan dan kejadian penting tanpa menulis secret ke log.

## Ringkasan

Alur pembayaran dasar terdiri dari membuat order, meminta URL checkout ke Midtrans, mengarahkan pengguna ke halaman gateway, dan memverifikasi status transaksi. Implementasi Laravel yang benar bergantung pada hal-hal kecil tetapi penting: sintaks PHP valid, nama konfigurasi tepat, field order konsisten, route cocok dengan method form, dan status berhasil ditentukan dari respons gateway.
