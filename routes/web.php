<?php

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/invoices/{invoice}/pdf', function (Invoice $invoice) {
    // 🔒 KEAMANAN: Pastikan user yang login adalah anggota dari tim pemilik invoice ini
    $user = Auth::user();
    if (! $user->teams->contains($invoice->team_id)) {
        abort(403, 'Anda tidak memiliki akses ke Invoice ini.');
    }

    // Load view blade dan passing data invoice
    $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice]);

    // Gunakan stream() agar PDF terbuka di browser (siap cetak), bukan otomatis terunduh
    return $pdf->stream('Invoice#0000' . $invoice->id . ' - ' . $invoice->client_name . ' - ' . $invoice->company . '.pdf');
})->middleware('auth')->name('invoices.pdf');
