@php
	// Ambil tim yang sedang aktif saat ini
	$tenant = filament()->getTenant();
@endphp

<!-- Pastikan tombol HANYA muncul jika user sedang berada di dalam sebuah Tim -->
@if ($tenant)
	<div class="flex items-center me-4">
		<x-filament::button href="{{ \App\Filament\Resources\Invoices\InvoiceResource::getUrl('create', ['tenant' => $tenant]) }}" tag="a" color="primary" size="sm" icon="heroicon-o-plus" tooltip="Buat Invoice Baru">
			Buat Invoice
		</x-filament::button>
	</div>
@endif
