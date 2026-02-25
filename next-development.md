# Next Development — Platform Kuis Multi-Jenjang

Dokumen ini berisi spesifikasi teknis pengembangan platform kuis dari arsitektur saat ini menuju sistem yang mendukung **Kelas, Guru per Mata Pelajaran, dan Dashboard berbasis Kelas**.

---

## Status Saat Ini (Sudah Diimplementasikan)

| Fitur | Status |
|---|---|
| Login multi-role (admin, teacher, student) | ✅ |
| Dashboard siswa dengan filter level | ✅ |
| Halaman profil & edit profil (guru + siswa) | ✅ |
| Kelola Nilai dengan tab SD/SMP/SMA | ✅ |
| Input nilai remedial (tidak hapus nilai lama) | ✅ |
| Export nilai ke CSV | ✅ |
| Upload/ganti avatar | ✅ |
| Anti-curang: `correct_option` disembunyikan saat kuis berlangsung | ✅ |
| Shuffle soal & opsi | ✅ |
| Timer per soal / per paket | ✅ |
| Review jawaban setelah kuis | ✅ |
| Analitik success rate per soal | ✅ |

---

## Fase Berikutnya — Sistem Kelas & Guru per Mata Pelajaran

### Latar Belakang

Saat ini guru hanya dibedakan berdasarkan **level jenjang** (SD/SMP/SMA). Kenyataannya di sekolah, guru memiliki **mata pelajaran spesifik** dan mengajar **kelas-kelas tertentu**. Misalnya:

- Bu Sari → Guru **Matematika** → Mengajar **Kelas 5A dan 5B**
- Pak Budi → Guru **Bahasa Indonesia** → Mengajar **Kelas 4A**

Nilai siswa juga perlu dikelompokkan **per kelas**, bukan hanya per jenjang.

---

## 1. Arsitektur Role (Revisi)

```
admin
 ├─ Kelola semua user, kategori, paket, soal
 ├─ Buat & kelola kelas (classes)
 └─ Assign guru ke kelas + mata pelajaran

teacher
 ├─ Punya 1+ mata pelajaran (teacher_subjects)
 ├─ Mengajar 1+ kelas (class_teachers)
 ├─ Dashboard: daftar kelas yang diajar
 ├─ Per kelas: lihat nilai siswa, assign paket
 └─ Input remedial per siswa

student
 ├─ Terdaftar di 1+ kelas (class_students)
 ├─ Dashboard: lihat kelas + guru + paket yang di-assign ke kelasnya
 └─ Lihat nilai miliknya sendiri
```

---

## 2. Desain Database Tambahan

### Tabel `classes`
```sql
CREATE TABLE `classes` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `name`          VARCHAR(100) NOT NULL,         -- e.g. "Kelas 5A"
  `level`         ENUM('sd','smp','sma') NOT NULL,
  `academic_year` VARCHAR(9) DEFAULT '2024/2025', -- e.g. "2024/2025"
  `is_active`     TINYINT(1) DEFAULT 1,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Tabel `teacher_subjects`
Mata pelajaran yang boleh diajar/dikelola oleh guru.
```sql
CREATE TABLE `teacher_subjects` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `teacher_id`  INT NOT NULL,
  `category_id` INT NOT NULL,               -- FK ke categories.id
  UNIQUE KEY `unique_teacher_subject` (`teacher_id`, `category_id`),
  FOREIGN KEY (`teacher_id`)  REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
);
```

### Tabel `class_teachers`
Relasi guru ↔ kelas ↔ mata pelajaran yang diajar di kelas itu.
```sql
CREATE TABLE `class_teachers` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `class_id`    INT NOT NULL,
  `teacher_id`  INT NOT NULL,
  `category_id` INT NOT NULL,               -- Mata pelajaran apa di kelas ini
  UNIQUE KEY `unique_class_teacher_subject` (`class_id`, `teacher_id`, `category_id`),
  FOREIGN KEY (`class_id`)    REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`)  REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
);
```

### Tabel `class_students`
Siswa yang terdaftar di kelas.
```sql
CREATE TABLE `class_students` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `class_id`   INT NOT NULL,
  `student_id` INT NOT NULL,
  `joined_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_class_student` (`class_id`, `student_id`),
  FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);
```

### Tabel `class_packages`
Paket kuis yang di-assign ke suatu kelas (oleh guru).
```sql
CREATE TABLE `class_packages` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `class_id`     INT NOT NULL,
  `package_id`   INT NOT NULL,
  `assigned_by`  INT NOT NULL,              -- teacher_id
  `assigned_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  `due_date`     DATETIME NULL,             -- batas waktu pengerjaan (opsional)
  UNIQUE KEY `unique_class_package` (`class_id`, `package_id`),
  FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);
```

### Tabel `users` — Kolom Tambahan
```sql
ALTER TABLE `users`
  ADD COLUMN `teacher_subject_ids` TEXT NULL COMMENT 'Deprecated — gunakan teacher_subjects table',
  ADD COLUMN `class_id` INT NULL COMMENT 'Kelas utama untuk siswa (denormalisasi)';
```

---

## 3. Workflow Lengkap

### 3.1 Setup oleh Admin
```
1. Buat kategori mata pelajaran (sudah ada)
2. Buat user guru → assign level + mata pelajaran (teacher_subjects)
3. Buat kelas (classes) → e.g., "Kelas 5A", "Kelas 6B"
4. Assign guru ke kelas per mata pelajaran (class_teachers)
5. Buat user siswa → assign ke kelas (class_students)
```

### 3.2 Workflow Guru
```
1. Login → redirect ke teacher_dashboard.php
2. Lihat daftar kelasnya (class_teachers join classes)
3. Klik kelas → lihat daftar siswa + nilai per paket
4. Assign paket kuis ke kelas (class_packages)
5. Input remedial per siswa
6. Lihat analitik: soal tersulit, rata-rata nilai per kelas
```

### 3.3 Workflow Siswa
```
1. Login → redirect ke student.php
2. Dashboard tampilkan:
   - Nama kelasnya (dari class_students)
   - Guru dan mata pelajaran (dari class_teachers)
   - Paket kuis yang di-assign (class_packages)
3. Kerjakan paket → nilai otomatis tersimpan dengan class_id
4. Dashboard tampilkan riwayat nilai miliknya
```

---

## 4. Halaman & File Baru yang Dibutuhkan

### Backend (API)
| File | Fungsi |
|---|---|
| `api_classes.php` | CRUD kelas, assign guru, assign siswa |
| `api_class_packages.php` | Assign / cabut paket ke kelas |

### Frontend — Admin Panel (panel baru di `admin.php`)
| Panel | Isi |
|---|---|
| `panel-classes` | DataTable kelas, tombol tambah/edit/hapus kelas |
| `panel-class-members` | Manage siswa & guru per kelas |

### Frontend — Halaman Baru
| File | Keterangan |
|---|---|
| `teacher_dashboard.php` | Dashboard guru: list kelas yang diajar |
| `class_detail.php` | Detail satu kelas: siswa, paket, nilai |

### Modifikasi File Existing
| File | Modifikasi |
|---|---|
| `student.php` | Tampilkan kelas dan guru siswa; filter paket dari `class_packages` |
| `quiz.php` | Simpan `class_id` di `quiz_sessions` saat kuis selesai |
| `api_session.php` | Tambah kolom `class_id` pada INSERT `quiz_sessions` |
| `api_grades.php` | Filter nilai per kelas, bukan hanya per level |
| `login.php` | Redirect guru ke `teacher_dashboard.php` |

---

## 5. Perubahan `quiz_sessions`

```sql
ALTER TABLE `quiz_sessions`
  ADD COLUMN `class_id` INT NULL COMMENT 'Kelas siswa saat mengerjakan kuis',
  ADD FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL;
```

---

## 6. Rencana Implementasi (Bertahap)

### Tahap 1 — Database & Admin Panel Kelas
- [ ] Buat tabel `classes`, `teacher_subjects`, `class_teachers`, `class_students`, `class_packages`
- [ ] Tambah panel "Kelola Kelas" di `admin.php`
- [ ] Tambah field "Mata Pelajaran" saat buat/edit user guru (multi-select kategori)

### Tahap 2 — Dashboard Guru
- [ ] Buat `teacher_dashboard.php`: tampilkan kelas yang diajar
- [ ] Buat `class_detail.php`: daftar siswa, yang sudah & belum kerjakan, nilai
- [ ] Fitur assign paket ke kelas dari halaman kelas
- [ ] Update `login.php`: guru redirect ke `teacher_dashboard.php`

### Tahap 3 — Pengalaman Siswa per Kelas
- [ ] Update `student.php`: tampilkan info kelas + guru + paket tugas
- [ ] Filter kuis di dashboard siswa hanya tampilkan yang ada di `class_packages`
- [ ] Simpan `class_id` di `quiz_sessions`

### Tahap 4 — Nilai per Kelas
- [ ] Update `api_grades.php`: tambah filter per `class_id`
- [ ] Update panel Kelola Nilai: tab per kelas (bukan hanya per jenjang)
- [ ] Remedial: kaitkan dengan kelas siswa

---

## 7. Fitur Profesional Lanjutan (Setelah Tahap 4)

### A. Notifikasi Tugas
Ketika guru assign paket ke kelas, siswa yang login melihat badge/notif "Ada tugas baru dari Pak Budi".

### B. Statistik Kelas
Di dashboard guru: grafik rata-rata nilai per paket, persentase siswa yang sudah mengerjakan.

### C. Mode Ujian Terkendali (CBT)
- Jadwal: paket hanya bisa dikerjakan dalam rentang waktu tertentu (`start_time`, `end_time` di `class_packages`)
- Anti-cheat: jawaban benar tidak dikirim ke client, hanya dikirim setelah sesi selesai

### D. Raport Digital
Export nilai siswa per kelas per mata pelajaran ke PDF atau Excel.

---

## 8. Arsitektur Lama (Masih Aktif)

Fitur saat ini yang tetap dipertahankan:
- Mode guest (tanpa login) tetap bisa mengakses paket `target_access = 'guest'`
- Admin tetap bisa kelola semua tanpa sistem kelas
- Guru/siswa tanpa kelas tetap bisa akses sesuai level (backward compatible)
