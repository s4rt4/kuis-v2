# Pengisian Detail Teknis Pengembangan Kuis (Next Development)

Dokumen ini berisi spesifikasi teknis mendalam untuk transformasi aplikasi Kuis SD menjadi platform kuis multi-jenjang yang mendukung mode Tamu (Guest) dan mode Ujian Terkendali (Internal).

## 1. Arsitektur Role & Autentikasi
Akan ada 7 role utama yang tersimpan dalam tabel `users`:
1.  **Administrator**: Akses ke seluruh bank soal, kategori, paket, dan data pengguna dari semua jenjang.
2.  **Guru SD, Guru SMP, Guru SMA**: Hanya dapat mengelola (CRUD) kategori, paket, dan soal yang sesuai dengan jenjang mereka.
3.  **Siswa SD, Siswa SMP, Siswa SMA**: Dapat mengerjakan kuis yang ditargetkan untuk jenjang mereka (baik mode internal maupun guest).

### Skema Tabel `users`
- `id` (INT, PK)
- `username` (VARCHAR)
- `password` (HASH)
- `role` (ENUM: 'admin', 'teacher', 'student')
- `level` (ENUM: 'sd', 'smp', 'sma', 'all')
- `full_name` (VARCHAR)

## 2. Sistem Targeting & Visibilitas
Setiap Paket Soal atau Soal individu akan memiliki atribut targeting:
- **`target_access`**: 
    - `guest`: Terbuka untuk semua tanpa login.
    - `internal`: Wajib login (untuk ujian sekolah).
    - `both`: Default, bisa diakses siapapun.
- **`target_level`**: (SD, SMP, SMA) - Digunakan untuk memfilter konten di halaman utama dinamis.

## 3. Dinamisiasi Homepage (Sisi Tamu)
`index.php` akan mengalami perubahan alur:
1.  **Level Picker**: Saat pertama dibuka, tamu memilih jenjang (Misal: Tombol SD yang berwarna cerah, SMP yang biru, SMA yang abu-abu/profesional).
2.  **Filtered Category**: Sidebar kategori hanya akan me-load data yang memiliki `target_level` sesuai pilihan tamu.
3.  **Filtered Packages**: Hanya menampilkan paket yang memiliki `target_access` = `guest` atau `both`.

## 4. Fitur Gambar & JSON Workflow
Mendukung input gambar tanpa merusak sistem "Paste JSON" yang sekarang:
1.  **Database**: Kolom `image_url` (TEXT) ditambahkan ke tabel `questions`.
2.  **Admin UI**: Di form tambah/edit soal, akan ada tombol "Upload Gambar". Setelah upload sukses, URL gambar otomatis dimasukkan ke kolom atau bisa di-copy ke dalam format JSON.
3.  **JSON Format**:
    ```json
    {
      "question_text": "Apa nama organ pada gambar?",
      "image_url": "assets/img/questions/paru-paru.png",
      "option_a": "Jantung",
      ...
    }
    ```
4.  **Frontend Render**: Jika `image_url` tidak kosong, card kuis akan menampilkan elemen `<img>` di atas atau di samping teks soal.

## 5. Pengaturan Timer Kontrol Guru
Guru dapat mengatur batasan waktu per paket soal di Admin Panel:
- **`timer_type`**: `none`, `per_packet` (misal 60 menit total), `per_question` (misal 30 detik per soal).
- **`timer_duration`**: (INT) Durasi dalam detik/menit.
- Timer ini akan otomatis muncul sebagai countdown saat kuis dimulai (CBT mode).

## 6. Pertimbangan Teknis Ujian Sekolah (CBT)
Untuk memastikan stabilitas saat digunakan ujian di laboratorium komputer:
- **State Persistence**: Progres pengerjaan disimpan ke `localStorage` (sisi klien) dan `quiz_sessions` (sisi server) setiap menjawab satu soal, sehingga jika komputer mati mendadak, progres tidak hilang.
- **Minimalist Mode**: Kurangi animasi berat (GSAP) khusus untuk paket bertarget `internal` guna menghemat resources browser lawas.

## 7. Fitur Profesional Tambahan (Low Refactor)
Fitur-fitur ini dapat ditambahkan tanpa merombak logika inti, namun memberikan nilai profesional tinggi:

### A. Shuffle Logic (Anti-Curang)
- **Logika**: Menambahkan field `is_shuffled` (BOOLEAN) pada tabel `packages`.
- **Implementasi**: Jika `true`, API `fetch_questions.php` akan mengembalikan data dengan `ORDER BY RAND()`.
- **Shuffle Option**: Dukungan acak pilihan jawaban (A, B, C, D) di sisi frontend sebelum dirender.

### B. Review Mode (Pembahasan)
- **Logika**: Setelah kuis selesai, selain skor, muncul tombol "Lihat Pembahasan".
- **Implementasi**: Aplikasi menampilkan kembali soal-soal beserta jawaban yang dipilih siswa, jawaban yang benar, dan isi dari kolom `explanation`.

### C. Analitik Keberhasilan (Success Rate)
- **Logika**: Mencatat setiap soal yang dijawab benar/salah ke dalam tabel `answer_logs`.
- **Dashboard Guru**: Menampilkan statistik "Soal Paling Sulit" (soal dengan tingkat kesalahan tertinggi) agar guru tahu materi mana yang perlu ditekankan kembali di kelas.
