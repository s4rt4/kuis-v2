# 🎓 Kuis Pintar - Platform Kuis Pembelajaran Profesional

**Kuis Pintar** adalah aplikasi kuis interaktif yang dirancang untuk mendukung pembelajaran di berbagai jenjang sekolah (SD, SMP, SMA). Aplikasi ini mendukung mode publik (Guest) untuk latihan mandiri dan mode terkendali (Internal) untuk keperluan ujian sekolah (CBT).

---

## ✨ Fitur Utama

### 🛡️ Sistem Role & Keamanan
*   **7 Role Pengguna**: Administrator, Guru (SD/SMP/SMA), dan Murid (SD/SMP/SMA).
*   **Akses Terpilih (Targeting)**: Konten dapat ditargetkan khusus untuk Tamu (`guest`), Siswa Login (`internal`), atau keduanya.
*   **Isolasi Konten**: Guru hanya dapat mengelola konten sesuai jenjangnya (SD/SMP/SMA).

### 📖 Pengalaman Siswa (CBT Ready)
*   **Multi-Jenjang**: Pemilih jenjang di halaman utama untuk memfilter mata pelajaran yang relevan.
*   **Shuffle Logic**: Pengacakan urutan soal dan pilihan jawaban untuk mencegah kecurangan.
*   **Timer Kontrol**: Batasan waktu pengerjaan yang diatur langsung oleh Guru/Admin.
*   **Pembahasan (Review Mode)**: Siswa dapat melihat penjelasan (`explanation`) setelah kuis selesai.

### 🛠️ Manajemen Konten (Admin)
*   **Bank Soal Multimedia**: Dukungan gambar pada soal melalui kolom `image_url`.
*   **Import/Export JSON**: Alur kerja cepat menggunakan sistem Paste JSON atau Upload File JSON.
*   **Dukungan MathJax**: Menampilkan rumus matematika yang kompleks dengan rapi.
*   **Analitik Guru**: Laporan soal yang paling sering dijawab salah untuk evaluasi materi.

---

## 🏗️ Teknologi yang Digunakan

*   **Frontend**: HTML5, CSS3, Vanilla JS, [Bootstrap 5](https://getbootstrap.com/), [GSAP](https://greensock.com/gsap/) (Animasi), [MathJax](https://www.mathjax.org/).
*   **Backend**: PHP 8+, MariaDB/MySQL.
*   **Database Integration**: PDO dengan sistem relasi `Categories -> Packages -> Questions`.

---

## 📂 Struktur Direktori Utama

| Path / File | Keterangan |
| :--- | :--- |
| `index.php` | Halaman utama dinamis (Guest/Student). |
| `admin.php` | Panel manajemen konten multi-role. |
| `quiz.php` | Engine kuis interaktif. |
| `next-development.md` | Panduan teknis pengembangan fitur selanjutnya. |
| `assets/` | File CSS, JS, Gambar, dan Audio. |

---

## 📈 Pembaruan Terbaru (Fitur Sistem V2)

Aplikasi Kuis Pintar telah diperbarui secara ekstensif dengan fitur-fitur baru berikut:

### 1. Database & Arsitektur Utama
- Penambahan role (`admin`, `teacher`, `student`) dan level (`sd`, `smp`, `sma`, `all`) pada pengguna.
- Penambahan target akses, target level, dan tipe timer pada pengaturan paket soal.
- Dukungan gambar (`image_url`) dan pembahasan (`explanation`) pada setiap soal.
- Penambahan tabel `quiz_sessions` untuk menyimpan state kuis (mencegah hilang saat reload) dan `answer_logs` untuk melacak tingkat keberhasilan soal.

### 2. Autentikasi & Routing
- Login system berbasis role yang mengarahkan pengguna ke halaman yang sesuai (Admin Panel atau Halaman Utama siswa).
- Filter pintar pada halaman utama memastikan pengguna Guest hanya melihat paket soal yang diizinkan (Guest/Both).

### 3. Peningkatan CBT Engine
- Dukungan tiga tipe pengerjaan: Timer Per Paket, Timer Per Soal (Pindah Otomatis), atau Tanpa Timer.
- Pencegahan pengintaian jawaban benar (`correct_option`) dan pembahasan melalui inspeksi jaringan oleh siswa biasa. Jawaban benar hanya dimuat ulang saat kuis selesai (Review Mode).
- Integrasi `localStorage` dan `api_session.php` untuk menyimpan progres pengerjaan secara real-time.

### 4. Tambahan Admin Panel
- Dukungan upload gambar soal baik melalui formulir (UI) maupun Import JSON secara masal (`image_url`).
- Integrasi modul **Statistik (Analytics)** untuk melihat Success Rate (Tingkat Keberhasilan) menjawab dari setiap soal di dalam suatu Paket.

---
*Dibuat untuk memajukan pendidikan melalui teknologi yang mudah diakses dan dikelola.*
