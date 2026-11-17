@extends('layouts.app')

@section('title', 'Putra Wijaya Berjaya | Logistics & Distribution')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pwb-contact-page.css') }}?v={{ filemtime(public_path('css/pwb-contact-page.css')) }}">
@endpush

@section('content')

{{-- ===== HERO ===== --}}
<section class="contact-hero">
    <div class="contact-hero__content">
        <div class="contact-tag"><span class="contact-tag__line"></span> Hubungi Kami</div>
        <h1>Siap menjadi mitra<br>logistik Anda</h1>
        <p>
            Punya pertanyaan tentang harga semen, armada, atau jadwal pengiriman? Hubungi tim kami dan kami akan segera membantu Anda.
        </p>
    </div>
</section>

{{-- ===== INFO CARDS (tabel contacts, category = info) ===== --}}
@php
    $icons = [
        'phone'    => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.8a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
        'email'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'location' => '<path d="M12 22s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ];
@endphp

@if ($contacts->isNotEmpty())
<section class="contact-info">
    <div class="contact-info__grid">
        @foreach ($contacts as $contact)
            <article class="contact-info__card">
                <div class="contact-info__icon">
                    <svg viewBox="0 0 24 24">{!! $icons[$contact->icon] ?? $icons['location'] !!}</svg>
                </div>
                <h3>{{ $contact->title }}</h3>
                <p>
                    @if ($contact->link)
                        <a href="{{ $contact->link }}" @if (str_starts_with($contact->link, 'http')) target="_blank" rel="noopener" @endif>{{ $contact->description }}</a>
                    @else
                        {{ $contact->description }}
                    @endif
                </p>
            </article>
        @endforeach
    </div>
</section>
@endif

{{-- ===== FORM + LOKASI ===== --}}
<section class="contact-main">
    <div class="contact-main__grid">

        {{-- Form --}}
        <div class="contact-form-card" id="kirim-pesan">
            <div class="contact-form-card__title">
                <span class="contact-tag__line"></span>
                <h2>Kirim Pesan</h2>
            </div>
            <p class="contact-form-card__lead">
                Isi form dibawah ini dan tim kami akan segera menghubungi Anda.
            </p>

            @if (session('success'))
            <div class="contact-alert contact-alert--success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('contact.store') }}">
                @csrf

                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required>
                @error('nama') <div class="contact-error">{{ $message }}</div> @enderror

                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', request('email')) }}" required>
                @error('email') <div class="contact-error">{{ $message }}</div> @enderror

                <label for="telepon">No Telepon</label>
                <input type="tel" id="telepon" name="telepon" value="{{ old('telepon') }}">
                @error('telepon') <div class="contact-error">{{ $message }}</div> @enderror

                <label for="subjek">Subjek</label>
                <input type="text" id="subjek" name="subjek" value="{{ old('subjek') }}" required>
                @error('subjek') <div class="contact-error">{{ $message }}</div> @enderror

                <label for="pesan">Pesan</label>
                <textarea id="pesan" name="pesan" rows="5" required>{{ old('pesan') }}</textarea>
                @error('pesan') <div class="contact-error">{{ $message }}</div> @enderror

                <button type="submit" class="contact-btn">
                    Kirim Pesan <span>&#8594;</span>
                </button>
            </form>
        </div>

        {{-- Lokasi (tabel contacts, category = location) --}}
        @if ($location)
        <aside class="contact-location-card">
            <h2>Lokasi Kami</h2>

            @if ($location->map_embed)
            <div class="contact-map">
                <iframe
                    src="{{ $location->map_embed }}"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="strict-origin-when-cross-origin"
                    title="Lokasi {{ $location->title }}"></iframe>
            </div>
            @endif

            <h3>{{ $location->title }}</h3>

            <div class="contact-address">
                <svg viewBox="0 0 24 24"><path d="M12 22s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.6" class="hole"/></svg>
                <p>{{ $location->description }}</p>
            </div>

            @if ($location->link)
            <a class="contact-maps-link" target="_blank" rel="noopener" href="{{ $location->link }}">
                Lihat Google Maps <span>&#8594;</span>
            </a>
            @endif

            <div class="contact-location-card__bar"></div>
        </aside>
        @endif

    </div>
</section>

@endsection
