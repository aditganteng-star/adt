<?php
/**
 * =====================================================================
 *  PENGATURAN WEBSITE — Hakuna Matata Course
 *  Ubah nilai di file ini sesuai kebutuhan. Tidak perlu mengubah file lain.
 * =====================================================================
 */

// ---- Database (bawaan XAMPP: user root, tanpa password) -------------
const DB_HOST = '127.0.0.1';
const DB_NAME = 'hakuna_learning';
const DB_USER = 'root';
const DB_PASS = '';

// ---- Identitas & kontak ---------------------------------------------
const SITE_NAME    = 'Hakuna Matata Course';
const SITE_TAGLINE = 'Belajar tenang, tumbuh hebat';
const SITE_CITY    = 'Krian, Sidoarjo, Jawa Timur';
const SITE_EMAIL   = 'halo@hakunamatatacourse.com';

// Nomor WhatsApp admin (format internasional tanpa +, tanpa spasi).
// GANTI dengan nomor resmi sebelum website dipublikasikan.
const WHATSAPP_NUMBER = '081292850231';

// ---- Login Google ----------------------------------------------------
// Isi dengan OAuth Client ID dari https://console.cloud.google.com/
// (APIs & Services → Credentials → OAuth client ID → Web application).
// Tambahkan http://localhost ke "Authorized JavaScript origins".
// Jika dikosongkan, tombol Google tampil dalam mode demo (hanya localhost).
const GOOGLE_CLIENT_ID = '';

// ---- Mode demo (matikan saat website dipublikasikan!) ---------------
const SHOW_DEMO_ACCOUNTS = false;   // tampilkan akun demo di halaman login
const SHOW_SAMPLE_NOTICE = false;   // tampilkan catatan "contoh data" pada tutor & harga
