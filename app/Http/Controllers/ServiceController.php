<?php

namespace App\Http\Controllers;

use App\Models\Service;

class ServiceController extends Controller
{
    /**
     * Halaman publik /services: keunggulan layanan + jenis armada dari tabel services.
     */
    public function service()
    {
        $services = Service::where('category', 'service')->orderBy('id')->get();
        $fleets = Service::where('category', 'fleet')->orderBy('id')->get();

        return view('services', compact('services', 'fleets'));
    }
}
