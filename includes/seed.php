<?php
/**
 * Data contoh (demo). Dipanggil oleh setup.php hanya saat database masih kosong.
 * SEMUA nama tutor, harga, dan materi di sini adalah CONTOH — ganti lewat panel admin.
 */
declare(strict_types=1);

function seed_demo_data(): void
{
    $pdo = db();
    $pdo->beginTransaction();

    /* ---------- Akun demo ---------- */
    $users = [
        // nama, email, password, peran, jenjang, sekolah
        ['Admin Hakuna',       'admin@hakuna.test',   'Admin123!', 'admin',   null,  null],
        ['Kak Rina Pratama',   'tutor@hakuna.test',   'Tutor123!', 'tutor',   null,  null],
        ['Kak Dimas Wijaya',   'tutor2@hakuna.test',  'Tutor123!', 'tutor',   null,  null],
        ['Adit Nugraha',       'murid@hakuna.test',   'Murid123!', 'student', 'SMP', 'SMPN 1 Krian'],
        ['Salsa Nabila',       'salsa@hakuna.test',   'Murid123!', 'student', 'SMP', 'SMPN 2 Krian'],
        ['Bima Prasetyo',      'bima@hakuna.test',    'Murid123!', 'student', 'SMA', 'SMAN 1 Krian'],
    ];
    $uid = [];
    foreach ($users as [$name, $email, $pass, $role, $grade, $school]) {
        $uid[$email] = insert('users', [
            'name' => $name, 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'role' => $role, 'grade_level' => $grade, 'school' => $school,
        ]);
    }

    /* ---------- Profil tutor (halaman publik) ---------- */
    $tutors = [
        ['Kak Rina Pratama',  'Matematika',            'Universitas Negeri Surabaya', 'S1 Pendidikan Matematika', 'Sabar menjelaskan konsep dari nol sampai paham, suka memakai contoh sehari-hari.', 'Mentor olimpiade sains tingkat kabupaten', '4 tahun mengajar', 'SMP,SMA', 'orange', $uid['tutor@hakuna.test']],
        ['Kak Dimas Wijaya',  'Fisika & Persiapan UTBK', 'Institut Teknologi Sepuluh Nopember', 'S1 Teknik Fisika', 'Fokus pada strategi mengerjakan soal UTBK dan membangun logika berpikir.', 'Lolos UTBK Saintek dengan nilai tinggi', '3 tahun mengajar', 'SMA,UTBK', 'blue', $uid['tutor2@hakuna.test']],
        ['Kak Nadia Safitri', 'Bahasa Inggris',        'Universitas Airlangga', 'S1 Sastra Inggris', 'Membuat belajar grammar dan speaking terasa santai tapi tetap terarah.', 'Skor IELTS 7.5', '5 tahun mengajar', 'SD,SMP,SMA', 'purple', null],
        ['Kak Fajar Ramadhan','Biologi & Kimia',       'Universitas Brawijaya', 'S1 Biologi', 'Menghubungkan konsep sains dengan fenomena di sekitar agar mudah diingat.', 'Juara karya ilmiah remaja', '3 tahun mengajar', 'SMP,SMA', 'green', null],
        ['Kak Ayu Lestari',   'Calistung & Matematika SD', 'Universitas Negeri Surabaya', 'S1 PGSD', 'Belajar lewat permainan dan cerita, cocok untuk adik-adik SD.', 'Guru berprestasi tingkat kecamatan', '6 tahun mengajar', 'SD', 'pink', null],
        ['Kak Reza Mahendra', 'Ekonomi & Bahasa Indonesia', 'Universitas Negeri Malang', 'S1 Pendidikan Ekonomi', 'Membantu menyusun jawaban yang runtut dan memahami bacaan dengan cepat.', 'Finalis debat tingkat nasional', '4 tahun mengajar', 'SMP,SMA,UTBK', 'teal', null],
    ];
    foreach ($tutors as $i => [$name, $subject, $univ, $major, $bio, $ach, $exp, $levels, $color, $userId]) {
        insert('tutor_profiles', [
            'user_id' => $userId, 'name' => $name, 'subject' => $subject, 'university' => $univ, 'major' => $major,
            'bio' => $bio, 'achievement' => $ach, 'experience' => $exp, 'levels' => $levels, 'color' => $color, 'sort_order' => $i,
        ]);
    }

    /* ---------- Paket ---------- */
    $packages = [
        // slug, nama, kategori, jenjang, deskripsi, fitur, harga, harga coret, satuan, badge
        ['les-sd',    'Les Reguler SD',      'les',    'SD',   'Pendampingan materi sekolah dan latihan dasar yang menyenangkan.', "Tatap muka 2x seminggu\nMateri & latihan online\nLaporan perkembangan bulanan\nDashboard murid & orang tua", 250000, 300000, 'bulan', null],
        ['les-smp',   'Les Reguler SMP',     'les',    'SMP',  'Penguatan konsep, latihan soal, dan persiapan ujian sekolah.', "Tatap muka 2x seminggu\nBank soal & pembahasan\nTryout bulanan gratis\nAbsensi & tugas online", 300000, 360000, 'bulan', 'Terlaris'],
        ['les-sma',   'Les Reguler SMA',     'les',    'SMA',  'Belajar dengan target dan pemetaan materi per mata pelajaran.', "Tatap muka 2x seminggu\nPemetaan materi & target\nTryout bulanan gratis\nKonsultasi jurusan", 350000, 420000, 'bulan', null],
        ['privat-sma','Privat Intensif',     'les',    'SMA',  'Belajar 1 lawan 1 dengan tutor pilihan, jadwal fleksibel.', "Jadwal fleksibel\nTutor sesuai kebutuhan\nMateri disesuaikan\nLaporan tiap pertemuan", 600000, 700000, 'bulan', 'Premium'],
        ['tryout-utbk','Tryout UTBK Series', 'tryout', 'UTBK', 'Paket tryout UTBK digital dengan skor otomatis dan pembahasan.', "Soal bergaya UTBK\nSkor langsung keluar\nPembahasan tiap soal\nRiwayat nilai di dashboard", 99000, 149000, 'paket', 'Hemat 33%'],
        ['tryout-smp','Tryout Ujian SMP',    'tryout', 'SMP',  'Latihan soal ujian sekolah dengan pembahasan lengkap.', "Soal latihan ujian\nSkor langsung keluar\nPembahasan tiap soal\nRiwayat nilai di dashboard", 49000, 79000, 'paket', null],
        ['utbk-intensif','Intensif UTBK',    'utbk',   'UTBK', 'Program terstruktur untuk persiapan seleksi masuk PTN.', "Kelas TPS & Literasi\nTryout mingguan\nEvaluasi & strategi pribadi\nKonsultasi jurusan & kampus", 450000, 550000, 'bulan', null],
        ['utbk-premium','UTBK Premium',      'utbk',   'UTBK', 'Paket lengkap: kelas intensif, tryout series, dan mentoring pribadi.', "Semua fitur Intensif UTBK\nTryout UTBK Series\nMentoring 1 lawan 1\nPrioritas tutor terbaik", 650000, 800000, 'bulan', 'Paling lengkap'],
    ];
    $pid = [];
    foreach ($packages as $i => [$slug, $name, $cat, $level, $desc, $feat, $price, $old, $unit, $badge]) {
        $pid[$slug] = insert('packages', [
            'slug' => $slug, 'name' => $name, 'category' => $cat, 'level' => $level, 'description' => $desc,
            'features' => $feat, 'price' => $price, 'old_price' => $old, 'unit' => $unit, 'badge' => $badge, 'sort_order' => $i,
        ]);
    }

    /* ---------- Kelas, pendaftaran, pertemuan, absensi ---------- */
    $classes = [
        ['Matematika SMP Kelas 9', 'Matematika', 'SMP', 'tutor@hakuna.test',  'Senin & Kamis, 16.00–17.30', 'Aljabar, geometri, dan persiapan ujian sekolah.', 'MTK9SMP'],
        ['Persiapan UTBK Saintek', 'UTBK',       'UTBK','tutor2@hakuna.test', 'Selasa & Jumat, 19.00–20.30', 'Penalaran, literasi, dan strategi soal UTBK.', 'UTBK26'],
    ];
    $cid = [];
    foreach ($classes as [$name, $subject, $level, $tutorEmail, $schedule, $desc, $code]) {
        $cid[$code] = insert('classes', [
            'name' => $name, 'subject' => $subject, 'level' => $level, 'tutor_id' => $uid[$tutorEmail],
            'schedule' => $schedule, 'description' => $desc, 'join_code' => $code,
        ]);
    }
    foreach (['murid@hakuna.test', 'salsa@hakuna.test'] as $em) insert('enrollments', ['class_id' => $cid['MTK9SMP'], 'student_id' => $uid[$em]]);
    insert('enrollments', ['class_id' => $cid['UTBK26'], 'student_id' => $uid['bima@hakuna.test']]);
    insert('enrollments', ['class_id' => $cid['UTBK26'], 'student_id' => $uid['murid@hakuna.test']]);

    $topics = ['Bilangan & Operasi', 'Persamaan Linear', 'Bangun Datar', 'Perbandingan'];
    $statusPool = ['Hadir', 'Hadir', 'Hadir', 'Izin', 'Hadir', 'Sakit'];
    foreach (['MTK9SMP', 'UTBK26'] as $code) {
        $students = rows('SELECT student_id FROM enrollments WHERE class_id = ?', [$cid[$code]]);
        foreach ($topics as $i => $topic) {
            $sid = insert('class_sessions', [
                'class_id' => $cid[$code], 'title' => 'Pertemuan ' . ($i + 1) . ': ' . $topic,
                'session_date' => date('Y-m-d', strtotime('-' . ((4 - $i) * 7) . ' days')),
                'attend_code' => random_code(5), 'is_open' => 0,
            ]);
            foreach ($students as $k => $st) {
                insert('attendance', ['session_id' => $sid, 'student_id' => $st['student_id'],
                    'status' => $statusPool[($i + $k) % count($statusPool)], 'method' => 'tutor']);
            }
        }
        // Pertemuan hari ini — absensi dengan kode sedang dibuka.
        insert('class_sessions', ['class_id' => $cid[$code], 'title' => 'Pertemuan 5: Latihan Soal Campuran',
            'session_date' => date('Y-m-d'), 'attend_code' => 'HAKUNA', 'is_open' => 1]);
    }

    /* ---------- Materi, tugas, pengumuman ---------- */
    $mat = [
        ['MTK9SMP', 'Rangkuman Persamaan Linear', 'Ringkasan rumus dan contoh soal bertahap.', 'link', 'https://id.wikipedia.org/wiki/Persamaan_linear', 'tutor@hakuna.test'],
        ['MTK9SMP', 'Video: Teorema Pythagoras', 'Penjelasan visual teorema Pythagoras.', 'video', 'https://www.youtube.com/results?search_query=teorema+pythagoras', 'tutor@hakuna.test'],
        ['UTBK26',  'Strategi Penalaran Umum', 'Tips menyelesaikan soal penalaran dengan cepat.', 'link', 'https://snpmb.id/', 'tutor2@hakuna.test'],
    ];
    foreach ($mat as [$code, $title, $desc, $kind, $link, $by]) {
        insert('materials', ['class_id' => $cid[$code], 'title' => $title, 'description' => $desc, 'kind' => $kind, 'url' => $link, 'created_by' => $uid[$by]]);
    }
    $a1 = insert('assignments', ['class_id' => $cid['MTK9SMP'], 'title' => 'Latihan Persamaan Linear', 'description' => "Kerjakan 5 soal berikut dan tulis langkah penyelesaiannya:\n1) 2x + 3 = 11\n2) 5x - 7 = 18\n3) 3(x - 2) = 12\n4) x/4 + 1 = 5\n5) 7 - 2x = 1", 'due_date' => date('Y-m-d', strtotime('+5 days')), 'created_by' => $uid['tutor@hakuna.test']]);
    insert('assignments', ['class_id' => $cid['MTK9SMP'], 'title' => 'Proyek Mini: Luas Ruangan', 'description' => 'Ukur satu ruangan di rumahmu dan hitung luas serta kelilingnya.', 'due_date' => date('Y-m-d', strtotime('+12 days')), 'created_by' => $uid['tutor@hakuna.test']]);
    insert('assignments', ['class_id' => $cid['UTBK26'], 'title' => 'Latihan Penalaran Kuantitatif', 'description' => 'Selesaikan 10 soal deret dan perbandingan dari buku latihan halaman 12.', 'due_date' => date('Y-m-d', strtotime('+6 days')), 'created_by' => $uid['tutor2@hakuna.test']]);
    insert('submissions', ['assignment_id' => $a1, 'student_id' => $uid['salsa@hakuna.test'], 'answer' => '1) x=4  2) x=5  3) x=6  4) x=16  5) x=3', 'score' => 95, 'feedback' => 'Hebat, semua benar. Pertahankan!', 'status' => 'Dinilai']);
    insert('announcements', ['class_id' => $cid['MTK9SMP'], 'title' => 'Selamat datang di kelas!', 'body' => 'Jangan lupa isi absensi dengan kode dari tutor di awal pertemuan, ya.', 'created_by' => $uid['tutor@hakuna.test']]);

    /* ---------- Tryout & soal ---------- */
    seed_tryouts($uid['admin@hakuna.test'], $pid);

    $pdo->commit();
}

/** Bank soal contoh untuk tryout. Format: [subtes, soal, [A,B,C,D], jawaban, pembahasan]. */
function seed_tryouts(int $adminId, array $pid): void
{
    $sets = [
        [
            'title' => 'Tryout Gratis UTBK — Mini TPS', 'category' => 'UTBK', 'minutes' => 10, 'package' => null, 'public' => 1,
            'desc'  => 'Coba 6 soal TPS gratis untuk merasakan suasana tryout. Hasil langsung keluar!',
            'q' => [
                ['Penalaran Kuantitatif', 'Lanjutkan deret berikut: 2, 6, 18, 54, …', ['108', '148', '162', '216'], 'C', 'Setiap suku dikali 3. 54 × 3 = 162.'],
                ['Penalaran Kuantitatif', 'Jika 3x + 5 = 20, maka nilai x² adalah …', ['15', '20', '25', '30'], 'C', '3x = 15 sehingga x = 5, maka x² = 25.'],
                ['Penalaran Umum', 'Semua pelajar rajin membaca. Dina adalah pelajar. Kesimpulan yang tepat adalah …', ['Dina tidak rajin membaca', 'Dina rajin membaca', 'Sebagian pelajar tidak rajin', 'Dina bukan pelajar'], 'B', 'Dari premis umum, setiap pelajar (termasuk Dina) rajin membaca.'],
                ['Bahasa Indonesia', 'Kalimat berikut yang menggunakan kata baku adalah …', ['Dia merubah jadwal belajarnya.', 'Dia mengubah jadwal belajarnya.', 'Dia ngubah jadwal belajarnya.', 'Dia merobah jadwal belajarnya.'], 'B', 'Bentuk baku dari kata dasar "ubah" adalah "mengubah".'],
                ['Bahasa Inggris', 'She ___ to school every day.', ['go', 'going', 'goes', 'gone'], 'C', 'Subjek orang ketiga tunggal (she) memakai verb + s pada simple present.'],
                ['Penalaran Kuantitatif', 'Harga sebuah buku Rp80.000 mendapat diskon 25%. Harga yang dibayar adalah …', ['Rp55.000', 'Rp60.000', 'Rp65.000', 'Rp70.000'], 'B', 'Diskon 25% = Rp20.000, sehingga harga bayar Rp60.000.'],
            ],
        ],
        [
            'title' => 'Tryout UTBK Series 1', 'category' => 'UTBK', 'minutes' => 25, 'package' => $pid['tryout-utbk'], 'public' => 0,
            'desc'  => '12 soal bergaya UTBK: penalaran, pemahaman bacaan, dan bahasa Inggris.',
            'q' => [
                ['Penalaran Kuantitatif', '20% dari 250 adalah …', ['40', '45', '50', '55'], 'C', '0,2 × 250 = 50.'],
                ['Penalaran Kuantitatif', 'Rata-rata lima bilangan adalah 12. Jumlah empat bilangan pertama 44. Bilangan kelima adalah …', ['14', '16', '18', '20'], 'B', 'Jumlah kelima bilangan = 60, sehingga bilangan kelima = 60 − 44 = 16.'],
                ['Penalaran Kuantitatif', 'Sebuah mobil melaju 60 km/jam. Waktu untuk menempuh 150 km adalah …', ['2 jam', '2,5 jam', '3 jam', '3,5 jam'], 'B', 'Waktu = jarak ÷ kecepatan = 150 ÷ 60 = 2,5 jam.'],
                ['Penalaran Kuantitatif', 'Perbandingan umur ayah dan anak 7 : 2 dengan selisih 25 tahun. Umur anak adalah …', ['5 tahun', '8 tahun', '10 tahun', '14 tahun'], 'C', 'Selisih 5 bagian = 25, jadi 1 bagian = 5. Umur anak = 2 × 5 = 10.'],
                ['Penalaran Umum', 'Jika hujan, maka jalan basah. Jalan tidak basah. Kesimpulannya …', ['Hujan', 'Tidak hujan', 'Mungkin hujan', 'Jalan kering karena panas'], 'B', 'Kontraposisi: jika jalan tidak basah, maka tidak hujan.'],
                ['Penalaran Umum', 'Lanjutkan pola: A, C, E, G, …', ['H', 'I', 'J', 'K'], 'B', 'Pola melompat satu huruf: A-C-E-G-I.'],
                ['Penalaran Umum', 'Semua kucing adalah hewan. Sebagian hewan berbulu. Pernyataan yang PASTI benar adalah …', ['Semua kucing berbulu', 'Sebagian kucing berbulu', 'Semua kucing adalah hewan', 'Tidak ada kucing berbulu'], 'C', 'Hanya premis pertama yang pasti benar; yang lain tidak dapat dipastikan.'],
                ['Pemahaman Bacaan', 'Makna kata "efektif" dalam kalimat "Metode belajar ini sangat efektif" adalah …', ['Boros waktu', 'Berhasil guna', 'Rumit', 'Lambat'], 'B', 'Efektif berarti ada efeknya / berhasil guna.'],
                ['Pemahaman Bacaan', 'Penulisan kata baku yang benar adalah …', ['analisa', 'analisis', 'analisys', 'analysis'], 'B', 'KBBI membakukan "analisis".'],
                ['Bahasa Inggris', 'If I ___ more time, I would study harder.', ['have', 'had', 'will have', 'having'], 'B', 'Conditional tipe 2 memakai past simple pada klausa if.'],
                ['Bahasa Inggris', 'Synonym of "begin" is …', ['finish', 'start', 'close', 'delay'], 'B', '"Begin" bersinonim dengan "start".'],
                ['Bahasa Inggris', 'He has lived here ___ 2020.', ['for', 'since', 'during', 'from'], 'B', '"Since" dipakai dengan titik waktu tertentu (2020).'],
            ],
        ],
        [
            'title' => 'Latihan Ujian Matematika SMP', 'category' => 'SMP', 'minutes' => 15, 'package' => $pid['tryout-smp'], 'public' => 0,
            'desc'  => '8 soal latihan matematika dasar untuk persiapan ujian sekolah SMP.',
            'q' => [
                ['Matematika', 'Hasil dari 3/4 + 1/8 adalah …', ['4/12', '7/8', '1', '5/8'], 'B', '3/4 = 6/8, sehingga 6/8 + 1/8 = 7/8.'],
                ['Matematika', 'Luas persegi panjang dengan panjang 12 cm dan lebar 5 cm adalah …', ['34 cm²', '60 cm²', '17 cm²', '120 cm²'], 'B', 'Luas = p × l = 12 × 5 = 60 cm².'],
                ['Matematika', 'KPK dari 4 dan 6 adalah …', ['6', '10', '12', '24'], 'C', 'Kelipatan 4: 4, 8, 12; kelipatan 6: 6, 12. KPK = 12.'],
                ['Matematika', 'Hasil dari (−3) × (−4) + 2 adalah …', ['−10', '−14', '10', '14'], 'D', '(−3) × (−4) = 12, lalu 12 + 2 = 14.'],
                ['Matematika', 'Keliling lingkaran berjari-jari 7 cm (π = 22/7) adalah …', ['22 cm', '44 cm', '154 cm', '49 cm'], 'B', 'K = 2πr = 2 × 22/7 × 7 = 44 cm.'],
                ['Matematika', 'Nilai x dari 2x − 7 = 9 adalah …', ['1', '4', '8', '16'], 'C', '2x = 16, sehingga x = 8.'],
                ['Matematika', 'Dua sudut segitiga besarnya 50° dan 60°. Sudut ketiga adalah …', ['60°', '70°', '80°', '90°'], 'B', 'Jumlah sudut segitiga 180°, jadi 180 − 110 = 70°.'],
                ['Matematika', '15% dari 200 adalah …', ['20', '25', '30', '35'], 'C', '0,15 × 200 = 30.'],
            ],
        ],
    ];
    foreach ($sets as $s) {
        $tid = insert('tryouts', [
            'title' => $s['title'], 'category' => $s['category'], 'description' => $s['desc'], 'duration_min' => $s['minutes'],
            'package_id' => $s['package'], 'is_public' => $s['public'], 'created_by' => $adminId,
        ]);
        foreach ($s['q'] as $i => [$subtest, $question, $opts, $correct, $why]) {
            insert('tryout_questions', [
                'tryout_id' => $tid, 'subtest' => $subtest, 'question' => $question,
                'option_a' => $opts[0], 'option_b' => $opts[1], 'option_c' => $opts[2], 'option_d' => $opts[3],
                'correct' => $correct, 'explanation' => $why, 'sort_order' => $i,
            ]);
        }
    }
    // Murid demo langsung punya akses ke tryout berbayar agar fiturnya bisa dicoba.
    $demoStudent = (int) val("SELECT id FROM users WHERE email = 'murid@hakuna.test'");
    foreach (['tryout-utbk', 'tryout-smp'] as $slug) insert('entitlements', ['user_id' => $demoStudent, 'package_id' => $pid[$slug]]);
}
