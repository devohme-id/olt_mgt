# Panduan Operasional & Pengujian End-to-End (E2E) Sistem NMS

Dokumen ini adalah panduan lengkap untuk Network Operations Center (NOC) atau System Administrator dalam mengoperasikan dan menguji sistem OLT Network Management (NMS) secara menyeluruh dari awal hingga akhir.

---

## DAFTAR ISI
1. [Persiapan Lingkungan (Pre-requisites)](#1-persiapan-lingkungan-pre-requisites)
2. [Skenario Pengujian 1: Inisiasi Sistem & Autentikasi](#2-skenario-pengujian-1-inisiasi-sistem--autentikasi)
3. [Skenario Pengujian 2: Manajemen OLT (Add & Sync)](#3-skenario-pengujian-2-manajemen-olt-add--sync)
4. [Skenario Pengujian 3: Manajemen ONU & Optical Power](#4-skenario-pengujian-3-manajemen-onu--optical-power)
5. [Skenario Pengujian 4: Simulasi Alarm & Notifikasi Telegram](#5-skenario-pengujian-4-simulasi-alarm--notifikasi-telegram)
6. [Skenario Pengujian 5: Pelaporan (Reporting)](#6-skenario-pengujian-5-pelaporan-reporting)
7. [Skenario Pengujian 6: Diagnostik Lanjutan (OID Explorer)](#7-skenario-pengujian-6-diagnostik-lanjutan-oid-explorer)

---

## 1. Persiapan Lingkungan (Pre-requisites)
Sebelum melakukan pengujian E2E, pastikan:
1. **Server NMS sedang berjalan:** Anda bisa mengakses dashboard web. (Jika lokal: `http://127.0.0.1:8000`).
2. **Konektivitas ke OLT:** Pastikan server NMS bisa melakukan proses `ping` ke IP OLT target dan Port UDP 161 (SNMP) terbuka. *(Baca panduan khusus: [Konfigurasi SNMP OLT HSGQ](HSGQ_SNMP_SETUP.md) agar OLT Anda siap dihubungkan).*
3. **Telegram Bot:** Pastikan `.env` memiliki `TELEGRAM_BOT_TOKEN` dan `TELEGRAM_CHAT_ID` yang valid jika ingin menguji notifikasi alarm.
4. **Daemon berjalan:** 
   - Di lokal Mac: pastikan `php artisan queue:work --queue=default,snmp-polling` berjalan di satu terminal (untuk background job/polling) dan `php artisan reverb:start` berjalan (untuk WebSocket/realtime).

---

## 2. Skenario Pengujian 1: Inisiasi Sistem & Autentikasi

**Tujuan:** Memastikan keamanan akses (Login) dan integritas navigasi awal.

* **Langkah 1:** Buka browser dan arahkan ke URL NMS (cth: `http://127.0.0.1:8000`). Sistem harus me-redirect ke halaman `/login`.
* **Langkah 2:** Masukkan kredensial admin default. (Email: `admin@oltnms.local`, Password: `admin123456`).
* **Langkah 3:** Klik "Log in". Anda harus diarahkan ke halaman **Dashboard**.
* **Validasi:** 
  - Pastikan Dashboard memuat angka-angka statistik.
  - Pastikan Sidebar menampilkan nama Anda dan role Administrator.

---

## 3. Skenario Pengujian 2: Manajemen OLT (Add & Sync)

**Tujuan:** Memastikan sistem dapat menambahkan perangkat keras OLT dan membaca spesifikasinya via SNMP.

* **Langkah 1:** Navigasi ke menu **"OLT Devices"** di sidebar.
* **Langkah 2:** Klik tombol **"Add New OLT"**.
* **Langkah 3:** Isi Form:
  - **Name:** "OLT Core Jakarta"
  - **IP Address:** Masukkan IP aktual OLT Anda (yaitu: `192.168.99.254` jika menggunakan VPN/LAN Routing, ATAU `IP Public` Anda jika menggunakan Port Forwarding).
  - **Vendor:** Pilih "HSGQ" atau "ZTE" sesuai perangkat Anda.
  - **SNMP Version:** Pilih `v2c`.
  - **SNMP Community:** Masukkan string community OLT Anda (contoh: `public`).
  - Klik **"Create OLT"**.
* **Langkah 4:** Setelah berhasil dibuat, klik nama OLT tersebut di tabel untuk masuk ke halaman **OLT Detail**.
* **Validasi:**
  - Anda akan melihat status OLT berubah dari `offline` menjadi `online` dalam 1-2 menit setelah *Poller daemon* berjalan di belakang layar.
  - Metrik CPU, Memory, dan Suhu (*Temperature*) harus memunculkan angka aktual dari perangkat.
  - Daftar **PON Ports** harus terisi otomatis (misal: EPON0/1, EPON0/2) sesuai dengan *hardware provisioning* otomatis dari SNMP sync.

---

## 4. Skenario Pengujian 3: Manajemen ONU & Optical Power

**Tujuan:** Memastikan deteksi ONU yang menempel pada PON Port dan pembacaan redaman kabel optik.

* **Langkah 1:** Di halaman **OLT Detail** yang Anda buat sebelumnya, klik tombol **"Sync ONUs"**. Sistem akan memicu *Queue Job* untuk membaca semua ONU yang teregistrasi di OLT tersebut.
* **Langkah 2:** Navigasi ke menu **"ONU Devices"** di sidebar.
* **Langkah 3:** Anda seharusnya melihat daftar ONU (berdasarkan MAC/SN) yang otomatis masuk ke dalam sistem.
* **Langkah 4:** Klik salah satu baris ONU. 
* **Validasi:**
  - Pada halaman ONU Detail, pastikan **Optical Rx Power** (contoh: `-22.50 dBm`) tampil.
  - Jika ONU sedang dimatikan pelanggan, statusnya harus menjadi `offline` atau `LOS` (Loss of Signal).

---

## 5. Skenario Pengujian 4: Simulasi Alarm & Notifikasi Telegram

**Tujuan:** Menguji kepekaan NMS terhadap masalah jaringan dan rute eskalasi (Notifikasi Telegram).

* **Langkah 1 (Simulasi):** Cabut sementara kabel fiber optik dari salah satu modem ONU yang sedang aktif (Atau *shutdown* port secara fisik dari OLT).
* **Langkah 2:** Tunggu siklus *polling* OLT (sekitar 1-3 menit tergantung konfigurasi `config/polling.php`).
* **Validasi 1 (Telegram):** Cek grup Telegram NOC Anda. Bot harus mengirim pesan dengan format:
  `🚨 [ACTIVE] ONU Alarm`
  `Message: Loss of Signal (LOS) detected`
* **Validasi 2 (Dashboard):** 
  - Buka halaman Dashboard NMS, kotak **"Active Alarms"** harus bertambah jumlahnya.
  - Buka menu **"Alarms"**. Pastikan ada *row* alarm baru dengan status `active` ber-badge merah `CRITICAL`.
* **Langkah 3 (Penyelesaian):** 
  - Colok kembali kabel fiber optik.
  - Klik tombol **"Ack"** pada tabel alarm untuk mengakui Anda sedang mengecek (*Acknowledged*).
  - Jika sinyal sudah normal kembali, sistem akan otomatis meresolve alarm (*Auto-clear*), atau Anda bisa klik manual **"Resolve"** dan mengisi catatan penyelesaian.

---

## 6. Skenario Pengujian 5: Pelaporan (Reporting)

**Tujuan:** Memastikan tim operasional dapat meng-*export* data untuk analisis lanjutan dan audit.

* **Langkah 1:** Navigasi ke menu **"Reports"**.
* **Langkah 2:** Cari panel **"ONU Optical Report"** dan klik tombol **"Download CSV"**.
* **Validasi 1:** File `.csv` akan terunduh. Buka dengan Excel/Numbers. Pastikan file berisi seluruh ONU, namun diurutkan dari yang sinyal *Rx Power*-nya paling jelek (misal dari `-30 dBm` naik ke `-18 dBm`).
* **Langkah 3:** Pada panel **"Alarm History Report"**, pilih tanggal *Start Date* 3 hari lalu, dan *End Date* hari ini. Klik **"Download CSV"**.
* **Validasi 2:** File CSV harus mendownload riwayat insiden alarm simulasi yang kita lakukan di Skenario 4.

---

## 7. Skenario Pengujian 6: Diagnostik Lanjutan (OID Explorer)

**Tujuan:** Menguji tools raw SNMP untuk *troubleshooting* manual.

* **Langkah 1:** Navigasi ke menu **"System" -> "OID Explorer"**.
* **Langkah 2:** Pada form "Target OLT", pilih OLT yang sudah kita tambahkan di Skenario 2.
* **Langkah 3:** Pada "Target OID", ketik: `.1.3.6.1.2.1.1.1.0` (Ini adalah standar OID untuk System Description / SysDescr).
* **Langkah 4:** Pilih metode `GET` dan klik **"Execute"**.
* **Validasi:** 
  - Tabel hasil harus muncul di bagian bawah.
  - OID harus terisi, Type harus string/text, dan Value harus memunculkan deskripsi *hardware* vendor secara langsung (contoh: `HSGQ-E04I EPON OLT ...`).
  - Tidak boleh memakan waktu lebih dari 5 detik (cek badge *Time: xx ms*).

---

**Selesai!** Jika semua 6 skenario ini berjalan dengan semestinya (tanpa error 500 dari sisi server dan sesuai validasi logikanya), maka NMS OLT System ini **Dinyatakan Layak Operasi / Lulus Uji E2E**. 
Tugas-tugas administratif rutin sekarang dapat dipantau langsung dari *Audit Log* (Menu System -> Audit Log) untuk memonitor aktivitas NOC.
