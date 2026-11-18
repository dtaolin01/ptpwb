@php $p = $pengiriman; @endphp
<!DOCTYPE html>
<html lang="id">
<body style="margin:0;padding:24px;background:#F4F7FC;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #DCE3F0;">
        <tr>
            <td style="background:#12285C;border-radius:12px 12px 0 0;padding:18px 24px;color:#ffffff;">
                <div style="font-size:16px;font-weight:bold;">PT PUTRA WIJAYA BERJAYA</div>
                <div style="font-size:12px;opacity:.8;">Laporan Pengiriman {{ $p->no_resi }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding:24px;font-size:14px;line-height:1.6;">
                <p style="margin:0 0 12px;">Yth. {{ $p->pelanggan?->nama ?? 'Bapak/Ibu' }},</p>

                @if ($pesan)
                    <p style="margin:0 0 12px;white-space:pre-line;">{{ $pesan }}</p>
                @else
                    <p style="margin:0 0 12px;">
                        Terima kasih telah menggunakan jasa PT Putra Wijaya Berjaya. Bersama email ini kami lampirkan
                        laporan pengiriman dengan nomor resi <b>{{ $p->no_resi }}</b>.
                    </p>
                @endif

                <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;font-size:13px;margin:8px 0 16px;border-top:1px solid #DCE3F0;">
                    <tr><td style="padding:6px 0;color:#64748b;width:120px;">No. Resi</td><td style="padding:6px 0;font-weight:bold;">{{ $p->no_resi }}</td></tr>
                    <tr><td style="padding:6px 0;color:#64748b;">Tujuan</td><td style="padding:6px 0;">{{ $p->tujuan }}</td></tr>
                    <tr><td style="padding:6px 0;color:#64748b;">Muatan</td><td style="padding:6px 0;">{{ $p->muatan }}</td></tr>
                    <tr><td style="padding:6px 0;color:#64748b;">Status</td><td style="padding:6px 0;">{{ $p->status_label }}</td></tr>
                </table>

                <p style="margin:0 0 12px;">Laporan lengkap ada pada lampiran PDF.</p>
                <p style="margin:0;">Hormat kami,<br><b>PT Putra Wijaya Berjaya</b></p>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 24px;border-top:1px solid #DCE3F0;font-size:11px;color:#94a3b8;">
                Jl. Raya Simpang Pasir RT 29 Samarinda · WhatsApp +62 852-1881-5858 · putrawijaya.berjaya@gmail.com
            </td>
        </tr>
    </table>
</body>
</html>
