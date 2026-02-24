# Pengisian Detail Teknis Pengembangan Kuis (Next Development)

Dokumen ini berisi spesifikasi teknis mendalam untuk transformasi aplikasi Kuis SD menjadi platform kuis multi-jenjang yang mendukung mode Tamu (Guest) dan mode Ujian Terkendali (Internal).

## 1. Arsitektur Role & Autentikasi
Akan ada 7 role utama yang tersimpan dalam tabel `users`:
1.  **Administrator**: Akses ke seluruh bank soal, kategori, paket, dan data pengguna dari semua jenjang.
2.  **Guru SD, Guru SMP, Guru SMA**: Hanya dapat mengelola (CRUD) kategori, paket, dan soal yang sesuai dengan jenjang mereka. (Catatan: Jika ada guru yang mengajar di lintas jenjang, akan dibuatkan multi-akun tersendiri, bukan melalui satu akun `level: all`).
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
2.  **Admin UI**: Di form tambah/edit soal, akan ada tombol "Upload Gambar". Validasi sisi server dan client (Ekstensi hanya `.png, .jpg, .webp` dan max limit 500KB). Setelah upload sukses, URL gambar otomatis dimasukkan ke kolom atau bisa di-copy ke dalam format JSON.
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
Aplikasi telah memiliki struktur dasar `time_limit` di tabel `packages`, akan diperkuat dengan alur UX berikut:
- **`timer_type`**: `none`, `per_packet` (misal 60 menit total), `per_question` (misal 30 detik per soal).
- **`timer_duration`**: Menggunakan kolom `time_limit` (INT) eksisting (dalam menit atau detik tergantung tipe timer).
- **Timer UX Logic**:
   - Mode `per_question`: Jika batas waktu soal habis, *state* akan memaksa pindah (*auto-next*) ke soal berikutnya, dan state terekam kosong/salah. Tombol "Kembali" (Back to previous question) akan dinonaktifkan *(disable)* untuk menghindari manipulasi durasi.
   - Timer otomatis muncul di header aplikasi saat (CBT mode) berlangsung.

## 6. Pertimbangan Teknis Ujian Sekolah (CBT)
Untuk memastikan stabilitas saat digunakan ujian di laboratorium komputer:
- **State Persistence**: Progres pengerjaan disimpan ke `localStorage` (sisi klien) dan `quiz_sessions` (sisi server) setiap menjawab satu soal, sehingga jika komputer mati mendadak, progres tidak hilang.
- **Minimalist Mode**: Kurangi animasi berat (GSAP) khusus untuk paket bertarget `internal` guna menghemat resources browser lawas.
- **Anti-Cheat (Data Scaping Protection)**: Pada mode ujian (internal), endpoint API (`fetch_questions.php`) **dilarang menyertakan kolom `correct_option` dan `explanation`** ke response JSON agar siswa tidak bisa menginspeksi jawaban melalui Network/Developer Tools. Response kunci jawaban hanya dikirim setelah sesi ujian dikunci (selesai).

## 7. Fitur Profesional Tambahan (Low Refactor)
Fitur-fitur ini dapat ditambahkan tanpa merombak logika inti, namun memberikan nilai profesional tinggi:

### A. Shuffle Logic (Anti-Curang)
- **Status Eksisting**: Aplikasi sudah punya kolom `shuffle_q` dan `shuffle_opt` di tabel `packages`.
- **Implementasi (Optimasi Beban Server)**: Data ditarik utuh tanpa `ORDER BY RAND()` dari db, melainkan menggunakan `shuffle()` array di proses PHP (`fetch_questions.php`) atau dirombak oleh *JavaScript* sisi Frontend. Ini menghemat *resource cost* yang berat bila query data berjumlah banyak secara acak *(randomization optimization)*.

### B. Review Mode (Pembahasan)
- **Logika**: Setelah kuis selesai, selain skor, muncul tombol "Lihat Pembahasan".
- **Implementasi**: API baru/khusus akan mengembalikan data kunci jawaban dan *explanation*, aplikasi menampilkan kembali soal-soal beserta jawaban yang dipilih siswa, jawaban yang benar.

### C. Analitik Keberhasilan (Success Rate)
- **Logika**: Mencatat setiap soal yang dijawab benar/salah ke dalam tabel `answer_logs`.
- **Dashboard Guru**: Menampilkan statistik "Soal Paling Sulit" (soal dengan tingkat kesalahan tertinggi) agar guru tahu materi mana yang perlu ditekankan kembali di kelas.
