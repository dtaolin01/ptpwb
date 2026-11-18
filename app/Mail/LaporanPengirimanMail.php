<?php

namespace App\Mail;

use App\Models\Pengiriman;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email laporan pengiriman untuk pelanggan, dengan lampiran PDF
 * (PDF dibuat di browser admin dari halaman "Laporan untuk Pelanggan").
 */
class LaporanPengirimanMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Pengiriman $pengiriman,
        public string $pdf,
        public ?string $pesan = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Laporan Pengiriman '.$this->pengiriman->no_resi.' - PT Putra Wijaya Berjaya',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.laporanPengiriman');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdf, self::namaFile($this->pengiriman))
                ->withMime('application/pdf'),
        ];
    }

    public static function namaFile(Pengiriman $p): string
    {
        return 'Laporan-Pengiriman-'.preg_replace('/[^A-Za-z0-9-]+/', '-', $p->no_resi).'.pdf';
    }
}
