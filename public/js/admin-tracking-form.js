// Form Tambah/Ubah Tracking (admin): pilih titik di peta + atur langkah perjalanan.
document.addEventListener('DOMContentLoaded', function () {
    // ---------- Langkah perjalanan ----------
    var tbody = document.getElementById('daftar-langkah');
    var tpl = document.getElementById('tpl-langkah');
    var counter = tbody ? tbody.querySelectorAll('tr.langkah').length + 100 : 0;

    function nomori() {
        tbody.querySelectorAll('tr.langkah .nomor').forEach(function (td, i) { td.textContent = i + 1; });
    }

    if (tbody && tpl) {
        document.getElementById('tambah-langkah').addEventListener('click', function () {
            var html = tpl.innerHTML.replace(/__i__/g, String(counter++));
            tbody.insertAdjacentHTML('beforeend', html);
            nomori();
            tbody.querySelector('tr.langkah:last-child input').focus();
        });

        tbody.addEventListener('click', function (e) {
            var btn = e.target.closest('.hapus-langkah');
            if (!btn) return;
            btn.closest('tr').remove();
            nomori();
        });
    }

    // ---------- Peta ----------
    var el = document.getElementById('peta-picker');
    if (!el || typeof L === 'undefined') return;

    var map = L.map(el, { scrollWheelZoom: false });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);
    map.on('click', function () { map.scrollWheelZoom.enable(); });
    map.on('mouseout', function () { map.scrollWheelZoom.disable(); });

    var gaya = {
        asal:   { cls: 'pwb-trk-marker--origin', isi: 'A', size: 32, nama: 'Asal' },
        tujuan: { cls: 'pwb-trk-marker--destination', isi: 'B', size: 32, nama: 'Tujuan' },
        posisi: { cls: 'pwb-trk-marker--truck', isi: '&#128666;', size: 40, nama: 'Posisi Truk' }
    };
    var markers = {};
    var aktif = 'asal';
    var garis = null;

    function ikon(g) {
        return L.divIcon({
            className: 'pwb-trk-marker-wrap',
            html: '<div class="pwb-trk-marker ' + g.cls + '">' + g.isi + '</div>',
            iconSize: [g.size, g.size],
            iconAnchor: [g.size / 2, g.size / 2]
        });
    }

    function baca(titik) {
        var lat = parseFloat(document.getElementById(titik + '_lat').value);
        var lng = parseFloat(document.getElementById(titik + '_lng').value);
        return (isNaN(lat) || isNaN(lng)) ? null : [lat, lng];
    }

    function tulis(titik, latlng) {
        document.getElementById(titik + '_lat').value = latlng ? latlng[0].toFixed(6) : '';
        document.getElementById(titik + '_lng').value = latlng ? latlng[1].toFixed(6) : '';
        gambar(titik);
    }

    function gambarGaris() {
        if (garis) { map.removeLayer(garis); garis = null; }
        var a = baca('asal'), b = baca('tujuan');
        if (a && b) {
            garis = L.polyline([a, b], { color: '#1553b5', weight: 3, opacity: 0.6, dashArray: '8 8' }).addTo(map);
        }
    }

    function gambar(titik) {
        var p = baca(titik);
        if (markers[titik]) { map.removeLayer(markers[titik]); delete markers[titik]; }
        if (p) {
            markers[titik] = L.marker(p, { icon: ikon(gaya[titik]), draggable: true, zIndexOffset: titik === 'posisi' ? 1000 : 0 })
                .addTo(map)
                .bindTooltip(gaya[titik].nama);
            markers[titik].on('dragend', function (e) {
                var ll = e.target.getLatLng();
                tulis(titik, [ll.lat, ll.lng]);
            });
        }
        gambarGaris();
    }

    function pilih(titik) {
        aktif = titik;
        document.querySelectorAll('.titik-btn').forEach(function (b) {
            var on = b.getAttribute('data-titik') === titik;
            b.classList.toggle('bg-pwb-navy', on);
            b.classList.toggle('text-white', on);
            b.classList.toggle('border-pwb-navy', on);
            b.classList.toggle('border-pwb-line', !on);
        });
    }

    document.querySelectorAll('.titik-btn').forEach(function (b) {
        b.addEventListener('click', function () { pilih(b.getAttribute('data-titik')); });
    });

    document.querySelectorAll('[data-hapus]').forEach(function (b) {
        b.addEventListener('click', function () { tulis(b.getAttribute('data-hapus'), null); });
    });

    ['asal', 'tujuan', 'posisi'].forEach(function (t) {
        ['_lat', '_lng'].forEach(function (s) {
            document.getElementById(t + s).addEventListener('change', function () { gambar(t); });
        });
        gambar(t);
    });

    map.on('click', function (e) { tulis(aktif, [e.latlng.lat, e.latlng.lng]); });

    // Tampilan awal: semua titik yang ada, atau Kalimantan Timur
    var titikAda = ['asal', 'tujuan', 'posisi'].map(baca).filter(Boolean);
    if (titikAda.length > 1) {
        map.fitBounds(L.latLngBounds(titikAda), { padding: [50, 50], maxZoom: 14 });
    } else if (titikAda.length === 1) {
        map.setView(titikAda[0], 12);
    } else {
        map.setView([-0.5022, 117.1536], 8);
    }
    pilih(baca('asal') ? (baca('tujuan') ? 'posisi' : 'tujuan') : 'asal');
    setTimeout(function () { map.invalidateSize(); }, 200);

    // ---------- Cari tempat (OpenStreetMap Nominatim) ----------
    var cari = document.getElementById('cari-lokasi');
    var info = document.getElementById('cari-info');

    function pesan(teks, error) {
        info.textContent = teks;
        info.classList.remove('hidden');
        info.className = 'mt-2 text-xs ' + (error ? 'text-red-600' : 'text-slate-500');
    }

    function jalankanCari() {
        var q = cari.value.trim();
        if (!q) return;
        pesan('Mencari "' + q + '"...');

        fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=id&q=' + encodeURIComponent(q), {
            headers: { 'Accept-Language': 'id' }
        })
            .then(function (r) { return r.json(); })
            .then(function (hasil) {
                if (!hasil.length) { pesan('Tempat tidak ditemukan. Coba nama lain atau klik langsung di peta.', true); return; }
                var p = [parseFloat(hasil[0].lat), parseFloat(hasil[0].lon)];
                tulis(aktif, p);
                map.setView(p, 13);
                pesan(gaya[aktif].nama + ' diatur ke: ' + hasil[0].display_name);
            })
            .catch(function () { pesan('Pencarian gagal. Klik langsung di peta.', true); });
    }

    document.getElementById('cari-btn').addEventListener('click', jalankanCari);
    cari.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); jalankanCari(); }
    });
});
