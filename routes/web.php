<?php

use App\Models\Invoice;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/welcome', function () {
    return view('welcome');
});

// Download Quotation
Route::get('/quotation/{quotation}/pdf', function (Quotation $quotation) {
    // 🔒 KEAMANAN: Pastikan user yang login adalah anggota dari tim pemilik invoice ini
    $user = Auth::user();
    if (! $user->teams->contains($quotation->team_id)) {
        abort(403, 'Anda tidak memiliki akses ke Quotation ini.');
    }

    // Load view blade dan passing data invoice
    $pdf = Pdf::loadView('quotation.pdf', ['quotation' => $quotation]);

    // Gunakan stream() agar PDF terbuka di browser (siap cetak), bukan otomatis terunduh
    return $pdf->stream('Quotation#0000' . $quotation->id . ' - ' . $quotation->client_name . ' - ' . $quotation->company . '.pdf');
})->middleware('auth')->name('quotation.pdf');

// Download Invoice
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
