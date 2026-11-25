// Tiket antrian yang diingat di perangkat pasien (localStorage) sampai antrian selesai,
// chip "Tiket saya" di halaman publik, dan unduhan kartu antrian sebagai gambar PNG.

const KUNCI = 'rs.tiket';

const aman = (fn, awal) => { try { return fn(); } catch (e) { return awal; } };
const baca = () => aman(() => { const d = JSON.parse(localStorage.getItem(KUNCI) || '[]'); return Array.isArray(d) ? d : []; }, []);
const tulis = (d) => aman(() => localStorage.setItem(KUNCI, JSON.stringify(d)));
const hariIni = () => { const n = new Date(); return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}-${String(n.getDate()).padStart(2, '0')}`; };

function simpan(t) {
    const daftar = baca().filter((x) => x.kode !== t.kode);
    daftar.unshift(t);
    tulis(daftar.slice(0, 5));
}

function hapus(kode) {
    tulis(baca().filter((x) => x.kode !== kode));
}

// ---------- halaman tiket: simpan, atau lepas bila sudah selesai ----------
const data = document.getElementById('tiketData');
const ini = data ? { ...data.dataset } : null;

if (ini) {
    if (ini.selesai === '1') {
        hapus(ini.kode);
    } else {
        simpan({ kode: ini.kode, nomor: ini.nomor, poli: ini.poli, tgl: ini.tgl, url: ini.url, statusUrl: ini.statusUrl });
    }
}

// ---------- chip "Tiket saya" ----------
async function periksa(t) {
    // Tiket hari lain tidak diingat lagi; selebihnya tanya server apakah sudah selesai
    if (t.tgl && t.tgl !== hariIni()) { hapus(t.kode); return false; }
    try {
        const r = await fetch(t.statusUrl, { headers: { Accept: 'application/json' } });
        if (r.status === 404) { hapus(t.kode); return false; }
        if (r.ok) {
            const j = await r.json();
            if (j.selesai) { hapus(t.kode); return false; }
        }
    } catch (e) { /* tanpa jaringan: tetap tampilkan tiket yang tersimpan */ }
    return true;
}

let chip = null;

// Semua tombol "Ambil nomor" berubah menjadi "Antrian saya" selama perangkat ini punya tiket aktif
// (skrip kecil di layout melakukan hal yang sama lebih dulu supaya tidak berkedip; data-asal-* menyimpan aslinya)
function ubahTombol(tiket) {
    document.querySelectorAll('a[href$="/antrian/ambil"], a[data-asal-href]').forEach((a) => {
        const node = [...a.childNodes].find((n) => n.nodeType === 3 && /ambil|antrian saya/i.test(n.nodeValue));
        if (!node) return;

        if (!tiket) {
            if (a.dataset.asalHref) { a.href = a.dataset.asalHref; node.nodeValue = a.dataset.asalTeks; delete a.dataset.asalHref; delete a.dataset.asalTeks; }
            return;
        }
        if (!a.dataset.asalHref) { a.dataset.asalHref = a.href; a.dataset.asalTeks = node.nodeValue; }
        a.href = tiket.url;
        node.nodeValue = 'Antrian saya';
    });
}

function gambarChip() {
    let daftar = baca().filter((t) => !t.tgl || t.tgl === hariIni());
    // di halaman tiket yang sedang dibuka, chip tidak perlu
    if (ini) daftar = daftar.filter((t) => t.kode !== ini.kode);

    ubahTombol(baca().filter((t) => !t.tgl || t.tgl === hariIni())[0]);

    // satu tiket cukup lewat tombol "Antrian saya"; chip hanya untuk memilih bila tiketnya lebih dari satu
    const semua = baca().filter((t) => !t.tgl || t.tgl === hariIni());
    if (semua.length < 2 || ini) { chip?.remove(); chip = null; return; }
    daftar = semua;

    if (!chip) {
        chip = document.createElement('div');
        chip.className = 'ts';
        document.body.appendChild(chip);
    }

    const utama = daftar[0];
    const banyak = daftar.length > 1;
    chip.innerHTML = `
        ${banyak ? `<div class="ts-list" hidden>${daftar.map((t) => `<a href="${t.url}"><b>${t.nomor}</b><span>${t.poli}</span></a>`).join('')}</div>` : ''}
        <${banyak ? 'button type="button"' : `a href="${utama.url}"`} class="ts-btn" aria-label="Buka tiket saya ${utama.nomor}">
            <i class="bi bi-ticket-perforated-fill" aria-hidden="true"></i>
            <span><small>Tiket saya</small><b>${utama.nomor}</b></span>
            ${banyak ? `<em>+${daftar.length - 1}</em>` : '<i class="bi bi-arrow-right-short" aria-hidden="true"></i>'}
        </${banyak ? 'button' : 'a'}>`;

    if (banyak) {
        const list = chip.querySelector('.ts-list');
        chip.querySelector('.ts-btn').addEventListener('click', () => { list.hidden = !list.hidden; });
    }
}

async function segarkan() {
    const daftar = baca();
    const hasil = await Promise.all(daftar.map(periksa));
    void hasil; // periksa() sudah menghapus yang selesai
    gambarChip();
}

gambarChip();
segarkan();
setInterval(() => { if (!document.hidden) segarkan(); }, 30000);
document.addEventListener('visibilitychange', () => { if (!document.hidden) segarkan(); });

// ---------- unduh kartu ----------
function rect(c, x, y, w, h, r) {
    c.beginPath();
    c.moveTo(x + r, y);
    c.arcTo(x + w, y, x + w, y + h, r);
    c.arcTo(x + w, y + h, x, y + h, r);
    c.arcTo(x, y + h, x, y, r);
    c.arcTo(x, y, x + w, y, r);
    c.closePath();
}

function pecah(c, teks, lebar) {
    const baris = [];
    let sisa = teks;
    while (sisa.length) {
        let n = sisa.length;
        while (n > 1 && c.measureText(sisa.slice(0, n)).width > lebar) n--;
        baris.push(sisa.slice(0, n));
        sisa = sisa.slice(n);
    }
    return baris;
}

async function buatKartu(d) {
    if (document.fonts?.ready) await document.fonts.ready;

    const W = 1080, H = 1500;
    const kanvas = document.createElement('canvas');
    kanvas.width = W; kanvas.height = H;
    const c = kanvas.getContext('2d');
    const huruf = getComputedStyle(document.body).fontFamily;
    const judul = getComputedStyle(document.querySelector('.numeral') || document.body).fontFamily;

    // latar
    const g = c.createLinearGradient(0, 0, W, H);
    g.addColorStop(0, '#08303D'); g.addColorStop(1, '#06202A');
    c.fillStyle = g; c.fillRect(0, 0, W, H);
    c.fillStyle = 'rgba(124,240,220,.12)'; c.beginPath(); c.arc(W - 80, 120, 360, 0, 7); c.fill();

    // kartu putih
    const kx = 70, ky = 70, kw = W - 140, kh = H - 140;
    c.fillStyle = '#fff'; rect(c, kx, ky, kw, kh, 48); c.fill();

    // kepala berwarna
    c.save(); rect(c, kx, ky, kw, 560, 48); c.clip();
    const gh = c.createLinearGradient(kx, ky, kx + kw, ky + 560);
    gh.addColorStop(0, '#073B4C'); gh.addColorStop(1, '#0A5A70');
    c.fillStyle = gh; c.fillRect(kx, ky, kw, 560); c.restore();

    c.textAlign = 'center';
    c.fillStyle = '#7CF0DC'; c.font = `700 34px ${huruf}`;
    c.fillText(d.rs.toUpperCase(), W / 2, ky + 90);
    c.fillStyle = 'rgba(255,255,255,.75)'; c.font = `500 32px ${huruf}`;
    c.fillText(`Tiket antrian ${d.poli}`, W / 2, ky + 145);
    c.fillStyle = '#fff'; c.font = `800 250px ${judul}`;
    c.fillText(d.nomor, W / 2, ky + 400);
    c.fillStyle = 'rgba(255,255,255,.7)'; c.font = `500 30px ${huruf}`;
    c.fillText('Simpan nomor ini sampai dipanggil', W / 2, ky + 490);

    // garis sobekan
    const sy = ky + 600;
    c.fillStyle = '#06202A';
    c.beginPath(); c.arc(kx, sy, 30, 0, 7); c.fill();
    c.beginPath(); c.arc(kx + kw, sy, 30, 0, 7); c.fill();
    c.strokeStyle = '#C9D6DA'; c.lineWidth = 4; c.setLineDash([14, 12]);
    c.beginPath(); c.moveTo(kx + 50, sy); c.lineTo(kx + kw - 50, sy); c.stroke(); c.setLineDash([]);

    // rincian
    const baris = [['Atas nama', d.nama], ['Poli', d.poli], ['Tanggal daftar', d.tanggal], ['Jam daftar', `${d.jam} WIB`]];
    let y = sy + 110;
    baris.forEach(([k, v]) => {
        c.textAlign = 'left'; c.fillStyle = '#6B7F86'; c.font = `500 30px ${huruf}`; c.fillText(k, kx + 70, y);
        c.textAlign = 'right'; c.fillStyle = '#0B2530'; c.font = `700 36px ${huruf}`; c.fillText(v, kx + kw - 70, y);
        c.strokeStyle = '#E4ECEE'; c.lineWidth = 2; c.beginPath(); c.moveTo(kx + 70, y + 32); c.lineTo(kx + kw - 70, y + 32); c.stroke();
        y += 100;
    });

    // petunjuk dan tautan untuk membuka kembali tiket
    c.textAlign = 'left'; c.fillStyle = '#0B2530'; c.font = `700 30px ${huruf}`;
    c.fillText('Buka kembali tiket ini', kx + 70, y + 20);
    c.fillStyle = '#51666E'; c.font = `500 26px ${huruf}`;
    pecah(c, 'Ganti perangkat atau ponsel bermasalah? Buka halaman Cek tiket lalu tempel tautan di bawah.', kw - 140).forEach((t, i) => c.fillText(t, kx + 70, y + 66 + i * 36));
    c.fillStyle = '#0A5A70'; c.font = `600 24px ui-monospace, Menlo, Consolas, monospace`;
    pecah(c, d.url, kw - 140).forEach((t, i) => c.fillText(t, kx + 70, y + 160 + i * 32));

    return kanvas;
}

document.addEventListener('click', async (e) => {
    const tombol = e.target.closest('#tiketUnduh');
    if (!tombol) return;
    const awal = tombol.innerHTML;
    tombol.disabled = true;
    try {
        const kanvas = await buatKartu(ini);
        const blob = await new Promise((res) => kanvas.toBlob(res, 'image/png'));
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `Tiket-${ini.nomor}-${ini.tgl}.png`;
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(() => URL.revokeObjectURL(a.href), 4000);
        tombol.innerHTML = '<i class="bi bi-check2 me-2" aria-hidden="true"></i>Kartu diunduh';
    } catch (err) {
        tombol.innerHTML = 'Gagal membuat kartu';
    }
    setTimeout(() => { tombol.innerHTML = awal; tombol.disabled = false; }, 2200);
});

document.addEventListener('click', async (e) => {
    const tombol = e.target.closest('#tiketSalin');
    if (!tombol) return;
    const awal = tombol.innerHTML;
    try { await navigator.clipboard.writeText(ini.url); tombol.innerHTML = '<i class="bi bi-check2 me-2" aria-hidden="true"></i>Tautan disalin'; }
    catch (err) { window.prompt('Salin tautan tiket:', ini.url); }
    setTimeout(() => { tombol.innerHTML = awal; }, 2000);
});
