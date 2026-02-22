# 📚 Kuis SD – Aplikasi Quiz untuk Sekolah Dasar

Proyek ini adalah aplikasi **Kuis Interaktif** berbasis web untuk anak Sekolah Dasar.  
Guru/Admin dapat mengelola soal, kategori, dan paket pelajaran.  
Siswa dapat mengerjakan kuis dengan tampilan menarik, animasi, dan suara feedback.

---

## ✨ Fitur

### 🎓 Frontend (Siswa)
- Sidebar kategori pelajaran (Matematika, Bahasa Indonesia, dll).
- Tiap kategori punya paket soal (contoh: Paket A, B, C).
- Soal ditampilkan dalam **card 2 kolom** agar nyaman dibaca anak-anak.
- Pilihan jawaban **langsung pindah ke soal berikutnya** (tanpa tombol "Next").
- Suara feedback: benar ✅ atau salah ❌.
- Nilai akhir ditampilkan dengan animasi mantul-mantul.
- Dukungan **MathJax** untuk menampilkan rumus matematika.

### 🛠 Backend (Admin)
- Kelola **Kategori** (tambah, edit, hapus).
- Kelola **Paket** (tambah, edit, hapus).
- Kelola **Soal** (tambah, edit, hapus).
- Form soal mendukung input rumus matematika.
- Tampilan admin menggunakan layout sama dengan frontend (sidebar + konten utama).
- Semua data tersimpan di **MySQL**.

---

## 🏗️ Teknologi yang Digunakan

- **Frontend**:  
  - HTML5, CSS3, JavaScript (modular, tanpa inline code).  
  - [Bootstrap](https://getbootstrap.com/) untuk layout dan komponen.  
  - [Font Awesome](https://fontawesome.com/) untuk ikon.  
  - [GSAP](https://greensock.com/gsap/) untuk animasi.  
  - [MathJax](https://www.mathjax.org/) untuk notasi matematika.

- **Backend**:  
  - PHP 8+  
  - MySQL/MariaDB  
  - PDO untuk koneksi database

- **Lingkungan Lokal**:  
  - [Laragon](https://laragon.org/) (direkomendasikan) atau XAMPP.


## 📂 Struktur Folder

| Path / File                 | Keterangan                              |
|-----------------------------|-----------------------------------------|
| `index.php`                 | Halaman utama (siswa)                   |
| `admin.php`                 | Halaman admin (UI + sidebar)            |
| `config.php`                | Konfigurasi database (PDO)              |
| `fetch_questions.php`       | API ambil soal untuk frontend           |
| `api_categories.php`        | API CRUD kategori                       |
| `api_packages.php`          | API CRUD paket                          |
| `api_questions.php`         | API CRUD soal                           |
|                             |                                         |
| `assets/`                   | Semua aset frontend/backend             |
| ├── `bootstrap/`            | Bootstrap CSS & JS (offline)            |
| ├── `fontawesome/`          | Font Awesome (offline)                  |
| ├── `gsap/`                 | GSAP (offline, animasi)                 |
| ├── `mathjax/`              | MathJax (offline, rumus matematika)     |
| ├── `css/`                  | Custom CSS (misal `style.css`)          |
| └── `js/`                   | JavaScript (misal `app.js`)             |



## 🗄️ Struktur Database

### Tabel: `categories`
| Kolom | Tipe Data     | Keterangan             |
|-------|---------------|------------------------|
| id    | INT (PK, AI)  | Primary key kategori   |
| name  | VARCHAR(100)  | Nama kategori (misal: Matematika, Bahasa Indonesia) |

---

### Tabel: `packages`
| Kolom       | Tipe Data     | Keterangan                               |
|-------------|---------------|------------------------------------------|
| id          | INT (PK, AI)  | Primary key paket                        |
| category_id | INT (FK)      | Relasi ke `categories.id`                |
| name        | VARCHAR(100)  | Nama paket (misal: Paket A, Semester 1)  |

---

### Tabel: `questions`
| Kolom          | Tipe Data     | Keterangan                                       |
|----------------|---------------|--------------------------------------------------|
| id             | INT (PK, AI)  | Primary key soal                                 |
| package_id     | INT (FK)      | Relasi ke `packages.id`                          |
| question_text  | TEXT          | Teks soal (bisa memuat MathJax untuk rumus)      |
| option_a       | VARCHAR(255)  | Pilihan jawaban A                                |
| option_b       | VARCHAR(255)  | Pilihan jawaban B                                |
| option_c       | VARCHAR(255)  | Pilihan jawaban C                                |
| option_d       | VARCHAR(255)  | Pilihan jawaban D                                |
| correct_option | CHAR(1)       | Jawaban benar (`A`, `B`, `C`, atau `D`)          |
| sort_order     | INT           | Urutan soal dalam satu paket                     |

---

## 🔗 Relasi Database
- **categories (1) → packages (n)**  
- **packages (1) → questions (n)**
