<div align="center">

<br/>

<img src="https://placehold.co/96x96/0B0D10/C9A227?text=L&font=playfair-display" width="96" alt="LUMINA logo" />

# L U M I N A

### *Digital Agency — crafted with light & precision.*

<br/>

![Laravel](https://img.shields.io/badge/Laravel-0B0D10?style=for-the-badge&logo=laravel&logoColor=C9A227)
![Blade](https://img.shields.io/badge/Blade-0B0D10?style=for-the-badge&logo=laravel&logoColor=FAF8F4)
![Alpine.js](https://img.shields.io/badge/Alpine.js-0B0D10?style=for-the-badge&logo=alpinedotjs&logoColor=8BC0D0)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-0B0D10?style=for-the-badge&logo=tailwindcss&logoColor=38BDF8)
![React](https://img.shields.io/badge/Admin-React_+_Inertia-0B0D10?style=for-the-badge&logo=react&logoColor=61DAFB)
![GrapesJS](https://img.shields.io/badge/GrapesJS-CMS-0B0D10?style=for-the-badge&logo=grapesjs&logoColor=C9A227)

<br/>

**[Tentang](#-tentang) · [Arsitektur](#-arsitektur-hibrida) · [Design System](#-design-system) · [Struktur](#-struktur-halaman) · [Instalasi](#-instalasi) · [Roadmap](#-roadmap)**

<br/>

</div>

---

## ✦ Tentang

**LUMINA Digital Agency** adalah website agensi digital berbasis **Laravel** dengan dua sisi yang bekerja berdampingan:

| | Sisi Publik | Sisi Admin |
|---|---|---|
| **Peran** | Website profil agensi untuk pengunjung | Panel CMS untuk mengelola konten & halaman |
| **Teknologi** | Laravel Blade + Alpine.js + Tailwind CSS | React + Inertia + GrapesJS |
| **Gaya** | Editorial, elegan, dark mode sebagai default | Fungsional, berbasis editor visual |

Frontend publik awalnya dibangun dengan React/TypeScript + Framer Motion, lalu dimigrasikan ke **Blade + Alpine.js** dengan paritas fungsional penuh: lebih ringan, lebih dekat ke server, dan lebih mudah dirawat.

---

## ✦ Arsitektur Hibrida

```mermaid
flowchart LR
    V([👤 Pengunjung]) --> P
    A([🛠 Admin]) --> C

    subgraph LARAVEL [" Laravel Application "]
        direction TB
        P["🌐 Frontend Publik<br/><b>Blade + Alpine.js + Tailwind</b>"]
        C["🧩 Panel Admin / CMS<br/><b>React + Inertia + GrapesJS</b>"]
        DB[("🗄 Database")]
        P --- DB
        C --- DB
    end

    style LARAVEL fill:#0B0D10,stroke:#C9A227,color:#FAF8F4
    style P fill:#14171c,stroke:#C9A227,color:#FAF8F4
    style C fill:#14171c,stroke:#C9A227,color:#FAF8F4
    style DB fill:#C9A227,stroke:#C9A227,color:#0B0D10
```

**Pergantian dari React ke Alpine.js (paritas fungsional):**

| Sebelumnya (React) | Sekarang (Blade + Alpine) |
|---|---|
| `useState` / state komponen | `x-data` |
| Framer Motion `whileInView` | `IntersectionObserver` |
| Framer Motion entrance animation | CSS `@keyframes` |
| Komponen TSX | Partial Blade |

---

## ✦ Design System

Satu bahasa visual untuk seluruh halaman — gelap, hangat, dan berkelas.

<div align="center">

| Ink | Paper | Brass |
|:---:|:---:|:---:|
| ![ink](https://placehold.co/140x70/0B0D10/0B0D10) | ![paper](https://placehold.co/140x70/FAF8F4/FAF8F4) | ![brass](https://placehold.co/140x70/C9A227/C9A227) |
| `#0B0D10` | `#FAF8F4` | `#C9A227` |
| Latar dark mode | Latar light mode | Aksen utama |

</div>

| Elemen | Pilihan |
|---|---|
| **Heading** | Fraunces *(serif)* |
| **Body** | Inter |
| **Mode default** | 🌙 Dark |
| **Dark mode** | Getter Alpine defensif yang seragam di semua komponen (bukan varian `dark:` Tailwind) |

---

## ✦ Struktur Halaman

Seluruh section publik berupa file Blade statis dengan nilai yang diambil langsung dari database, tanpa ketergantungan pada variabel `$content`.

```
resources/views/
├── sections/
│   ├── navbar          →  navigasi utama
│   ├── hero            →  sambutan & CTA
│   ├── tentang-kami    →  profil agensi
│   ├── layanan         →  layanan yang ditawarkan
│   ├── portfolio       →  karya pilihan
│   ├── blog            →  artikel & insight
│   ├── kontak          →  form & informasi kontak
│   └── footer          →  penutup & tautan
```

> Keputusan desain: section dibuat statis agar eksperimen UI tidak bergantung pada panel admin dan database.

---

## ✦ Tech Stack

<div align="center">

| Layer | Teknologi |
|:---:|:---|
| **Backend** | Laravel · PHP · MySQL |
| **Templating** | Blade |
| **Interaktivitas** | Alpine.js |
| **Styling** | Tailwind CSS |
| **Admin / CMS** | React · Inertia · GrapesJS |
| **Tipografi** | Fraunces · Inter |

</div>

---

## ✦ Instalasi

**Prasyarat:** PHP, Composer, Node.js & npm, serta database MySQL.

```bash
# 1. Clone repository
git clone <URL-REPOSITORY-ANDA> lumina
cd lumina

# 2. Install dependensi
composer install
npm install

# 3. Konfigurasi environment
cp .env.example .env
php artisan key:generate

# 4. Atur koneksi database di .env, lalu migrasi
php artisan migrate

# 5. Jalankan (dua terminal)
php artisan serve
npm run dev
```

Buka **http://localhost:8000** 🚀

---

## ✦ Roadmap

- [x] Migrasi frontend publik dari React/TypeScript ke Blade + Alpine.js
- [x] Penyeragaman design system (ink · paper · brass, Fraunces + Inter)
- [x] Konversi seluruh section ke Blade statis
- [ ] Hubungkan kembali section ke database (mulai dari `hero-section`)
- [ ] Lengkapi `section_contents` untuk section selain Hero
- [ ] Tambah tabel `settings` untuk konfigurasi global
- [ ] Hapus tabel bawaan Laravel yang tidak terpakai

---

## ✦ Lisensi

Dibangun di atas [Laravel](https://laravel.com) yang berlisensi [MIT](https://opensource.org/licenses/MIT).
Lisensi proyek LUMINA: *tentukan sendiri (mis. MIT / proprietary).*

<br/>

<div align="center">

**LUMINA Digital Agency**

<sub>Dibuat dengan ☕ dan Laravel</sub>

</div>
