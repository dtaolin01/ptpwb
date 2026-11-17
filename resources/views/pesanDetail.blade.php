@extends('layoutsAdmin.app')

@section('page_title', 'Detail Pesan')
@section('title', 'Detail Pesan')
@section('subtitle', 'Dikirim '.$pesan->created_at->format('d/m/Y H:i'))

@section('banner_action')
    <a href="{{ route('pesan') }}" class="inline-flex items-center gap-3 rounded-lg border border-pwb-navy px-5 py-3 text-sm font-bold text-pwb-navy hover:bg-white">
        <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
@endsection

@section('content')
    <div class="max-w-3xl rounded-xl border border-pwb-line bg-white p-6">
        <dl class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
            <div>
                <dt class="font-bold text-pwb-navy">Nama</dt>
                <dd class="mt-1">{{ $pesan->nama }}</dd>
            </div>
            <div>
                <dt class="font-bold text-pwb-navy">Email</dt>
                <dd class="mt-1"><a href="mailto:{{ $pesan->email }}?subject={{ rawurlencode('Re: '.$pesan->subjek) }}" class="text-pwb-blue hover:underline">{{ $pesan->email }}</a></dd>
            </div>
            <div>
                <dt class="font-bold text-pwb-navy">No Telepon</dt>
                <dd class="mt-1">{{ $pesan->telepon ?: '-' }}</dd>
            </div>
            <div>
                <dt class="font-bold text-pwb-navy">Subjek</dt>
                <dd class="mt-1">{{ $pesan->subjek }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="font-bold text-pwb-navy">Pesan</dt>
                <dd class="mt-1 whitespace-pre-line rounded-lg bg-slate-50 p-4 leading-relaxed">{{ $pesan->pesan }}</dd>
            </div>
        </dl>

        <div class="mt-6 flex gap-3">
            <a href="mailto:{{ $pesan->email }}?subject={{ rawurlencode('Re: '.$pesan->subjek) }}"
               class="rounded-lg bg-pwb-blue px-6 py-3 text-sm font-bold text-white hover:bg-blue-700">
                <i class="fa-solid fa-reply mr-2"></i>Balas via Email
            </a>
            <form method="POST" action="{{ route('pesan.destroy', $pesan) }}"
                  onsubmit="return confirm('Hapus pesan ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-red-200 px-6 py-3 text-sm font-bold text-red-600 hover:bg-red-50">Hapus</button>
            </form>
        </div>
    </div>
@endsection
