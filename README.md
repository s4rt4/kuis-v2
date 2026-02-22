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

## 📈 Roadmap Pengembangan
Informasi detail mengenai rencana pengembangan sistem role, targeting, dan fitur anti-curang dapat dilihat pada file [**next-development.md**](./next-development.md).

---
*Dibuat untuk memajukan pendidikan melalui teknologi yang mudah diakses dan dikelola.*
