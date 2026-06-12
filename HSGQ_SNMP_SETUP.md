# Panduan Konfigurasi SNMP pada OLT HSGQ

Agar **OLT Network Management System (NMS)** dapat berkomunikasi, mengambil data (monitoring), dan melakukan konfigurasi (provisioning) ke perangkat OLT HSGQ, Anda wajib mengaktifkan dan mengkonfigurasi layanan SNMP di sisi OLT. 

Ikuti langkah-langkah berikut sesuai dengan antarmuka web (Web GUI) bawaan OLT HSGQ Anda:

## 1. Akses Web GUI OLT
Buka browser Anda dan akses IP *management* dari OLT HSGQ Anda (yaitu `192.168.99.254` di VLAN 99), kemudian login sebagai administrator.

## 2. Navigasi ke Menu SNMP
Pada menu utama di bagian atas/samping, cari dan klik menu **Service**, kemudian pilih tab **SNMP**.

## 3. Konfigurasi Parameter SNMP
Sesuaikan parameter berikut agar *matching* dengan sistem NMS yang kita bangun:

### A. SNMP Trapserver (Opsional untuk Alarm Instan)
Fitur *Trap* digunakan agar OLT dapat "menembak" notifikasi error langsung ke NMS kita.
1. Klik tombol **Add** di bagian *SNMP Trapserver*.
2. **server ip**: Masukkan IP Address dari **Server Ubuntu NMS** Anda (yaitu: `192.168.100.1`).
   > **Note (Testing Lokal / Mac):** Jika Anda sedang menjalankan NMS di laptop (Mac) via jaringan publik/WiFi, OLT **tidak bisa** menjangkau laptop Anda karena terhalang NAT/Firewall. Anda bisa mengosongkan/mengabaikan bagian *Trapserver* ini sementara waktu. Sistem NMS kita menggunakan metode **Polling (menarik data)** sebagai mekanisme utama, sehingga *Trapserver* (mendorong data) bersifat opsional.
3. **trap port**: Isi dengan `162` (Standar port UDP untuk SNMP Trap).
4. **trap community**: Isi dengan `public`.

### B. SNMP Community (Wajib)
Bagian ini sangat krusial karena merupakan "password" agar NMS bisa membaca dan menulis konfigurasi ke OLT.
1. Klik tombol **Setting** di bagian *SNMP Community*.
2. **Read Community**: Isi dengan `public` (Atau ubah ke string rahasia Anda, namun pastikan string ini yang dimasukkan saat menambah OLT di web NMS).
3. **Write Community**: Isi dengan `private` (Atau ubah ke string rahasia Anda, NMS membutuhkan akses *write* untuk bisa me-reboot ONU atau mengganti profil).

### C. SNMP Port (Wajib)
1. Klik tombol **Setting** di bagian *SNMP Port*.
2. **SNMP port**: Pastikan terisi angka `161`. Ini adalah port standar yang digunakan oleh *Poller Engine* NMS kita untuk berkomunikasi.

## 4. Validasi di NMS
Setelah konfigurasi di atas disimpan di OLT:
1. Buka NMS Anda dan masuk ke menu **OLT Devices -> Add New OLT**.
2. Masukkan IP Management OLT.
3. Pilih SNMP Version: `v2c`.
4. Masukkan **SNMP Community**: `public` (sesuai dengan *Read Community* di atas).
5. Klik Save. Jika NMS bisa membaca *System Name* atau menarik jumlah PON port, maka konfigurasi berhasil!

---

## 5. Konfigurasi MikroTik NAT (Untuk NMS di Luar Jaringan / Cloud / WAN)

Karena NMS Anda berada di luar jaringan OLT / WAN (atau beda segmen), NMS butuh jalur routing untuk menembak IP OLT lokal (`192.168.99.254`). 

**Note Penting:** Jika NMS (`192.168.100.1`) dan OLT (`192.168.99.254`) terhubung via VPN (seperti WireGuard/IPSec) atau Inter-VLAN Routing di MikroTik, Anda **TIDAK PERLU NAT**. NMS bisa langsung menembak `192.168.99.254` asalkan ada rule `Forward` yang diizinkan di Firewall MikroTik.

Namun, jika NMS Anda benar-benar murni menumpang di Public IP MikroTik tanpa VPN, Anda harus mengkonfigurasi Port Forwarding (DST-NAT) di MikroTik dan memasukkan **IP Public MikroTik** di dalam web NMS.

### A. Jika hanya memiliki 1 OLT
Anda dapat melempar port 161 dari luar langsung ke port 161 OLT.
Jalankan perintah ini di Terminal MikroTik:
```routeros
/ip firewall nat
add action=dst-nat chain=dstnat dst-port=161 in-interface=ether1 protocol=udp to-addresses=192.168.99.254 to-ports=161
```
*(Ubah `in-interface=ether1` dengan interface internet/public Anda).*

Di NMS, saat **Add OLT**:
- **IP Address:** Isi dengan **IP Public MikroTik**.
- **SNMP Port:** `161`

### B. Jika memiliki banyak OLT (Lebih dari 1 di jaringan yang sama)
Karena IP Public hanya satu, Anda harus membedakan port dari luar. Misalnya OLT 1 port 1611, OLT 2 port 1612.
```routeros
/ip firewall nat
add action=dst-nat chain=dstnat dst-port=1611 protocol=udp to-addresses=192.168.99.254 to-ports=161
add action=dst-nat chain=dstnat dst-port=1612 protocol=udp to-addresses=192.168.99.253 to-ports=161
```

Di NMS, saat **Add OLT**:
- OLT 1: **IP Address** = `IP Public`, **SNMP Port** = `1611`
- OLT 2: **IP Address** = `IP Public`, **SNMP Port** = `1612`

> **Catatan SNMP Trap (Notifikasi Instan):**
> Jika Anda mengaktifkan *SNMP Trapserver* di OLT (Langkah 3A), maka `server ip` yang dimasukkan di OLT HSGQ harus berupa **IP Public dari Server NMS Anda**, agar OLT bisa melempar pesan UDP 162 kembali ke server NMS di Cloud.
