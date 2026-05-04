<!DOCTYPE html>
<html lang="id">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>QUO-0000{{ $quotation->id }} - {{ $quotation->client_name }} - {{ $quotation->company }}</title>
	<link rel="shortcut icon" href="/public/img/logo-no-text.png" type="image/x-icon">
	<style>
		@font-face {
			font-family: 'Plus Jakarta Sans';
			src: url('{{ storage_path('fonts/PlusJakartaSans-Regular.ttf') }}') format('truetype');
			font-weight: normal;
			font-style: normal;
		}

		@font-face {
			font-family: 'Plus Jakarta Sans';
			src: url('{{ storage_path('fonts/PlusJakartaSans-Bold.ttf') }}') format('truetype');
			font-weight: bold;
			font-style: normal;
		}

		@font-face {
			font-family: 'Plus Jakarta Sans';
			src: url('{{ storage_path('fonts/PlusJakartaSans-Italic.ttf') }}') format('truetype');
			font-weight: normal;
			font-style: italic;
		}

		@font-face {
			font-family: 'Plus Jakarta Sans';
			src: url('{{ storage_path('fonts/PlusJakartaSans-BoldItalic.ttf') }}') format('truetype');
			font-weight: bold;
			font-style: italic;
		}

		body {
			font-family: 'Plus Jakarta Sans', sans-serif;
			font-size: 13px;
			margin-left: 20px;
			margin-right: 20px;
		}

		.container {
			width: 100%;
		}

		.invoice-title {
			font-size: 18px;
			font-weight: bold;
			margin-top: 5px;
		}

		.status {
			padding: 0;
			font-size: 15px;
			color: #fff;
			text-align: center;
		}

		.bg-danger {
			background-color: #f4cccc;
			color: #ff0000;
		}

		.bg-success {
			background-color: #03c14f;
		}

		.bg-info {
			background-color: #2b7fff;
		}

		.bg-warning {
			background-color: #f59e0b;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 30px;
			/* margin-bottom: 30px; */
		}

		th {
			text-align: left;
			border: 1px;
		}

		td {
			padding: 5px;
			border: 1px;
		}

		.fw-bold {
			font-style: bold;
		}

		.text-center {
			text-align: center;
		}

		.text-end {
			text-align: right;
		}

		.border-top {
			border-top: 1px solid black;
		}

		.row {
			display: flex;
			flex-wrap: wrap;
			margin-right: -15px;
			margin-left: -15px;
			align-items: center;
		}

		.col-1 {
			width: 65%;
			float: left;
			box-sizing: border-box;
			padding-left: 15px;
			height: 90px;
			background-color: #f2ffee;
		}

		.col-3 {
			width: 30%;
			float: left;
			box-sizing: border-box;
			height: 90px;
		}
	</style>
</head>

<body>
	<div class="container">
		<!-- Header -->
		<img src="{{ public_path('img/Header-Surat.png') }}" width="100%" alt="" style="margin-bottom: 40px;">


		<div class="row">
			<div class="col-1">
				<p style="margin-bottom: -8px;" class="invoice-title">QUOTATION #0000{{ $quotation->id }}</p>
				<p style="margin-bottom: -8px;">Tanggal Quotation: {{ \Carbon\Carbon::parse($quotation->quo_date)->translatedFormat('d F Y') }}</p>
			</div>
			@php
				$colors = [
				    'sent' => 'bg-warning',
				    'rejected' => 'bg-danger',
				    'approved' => 'bg-info',
				    'invoiced' => 'bg-success',
				];
			@endphp
			<div class="status col-3 {{ $colors[$quotation->status] }}">
				<h1>{{ Str::upper($quotation->status) }}</h1>
			</div>
			<div style="clear: both;"></div>
		</div>

		<div>
			<h4 style="margin-bottom: 0;">Kepada:</h4>
			<div>{{ $quotation->client_name }}</div>
			<div>{{ $quotation->company }}</div>
			<div>{{ $quotation->client_address }}</div>
			<div>{{ $quotation->client_city }}</div>
			<div>{{ $quotation->client_country }}</div>
		</div>

		<div>
			<table>
				<thead>
					<tr>
						<th>No</th>
						<th>Nama Produk</th>
						<th>Jumlah</th>
						<th class="text-end">Harga Satuan (Rp)</th>
						<th class="text-end">Total (Rp)</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($quotation->items as $product)
						<tr>
							<td> {{ $loop->iteration }} </td>
							<td> {{ $product->item_name }} </td>
							<td> {{ $product->quantity }} </td>
							<td class="text-end"> {{ number_format($product->unit_price, 0, ',', '.') }} </td>
							<td class="text-end"> {{ number_format($product->subtotal, 0, ',', '.') }} </td>
						</tr>
					@endforeach
					<tr class="border-top">
						<td colspan="4" class="text-end">
							<h4 class="fw-bold" style="margin: 0;">
								Subtotal
							</h4>
						</td>
						<td>
							<h4 class="fw-bold text-end" style="margin: 0;">
								{{ number_format($quotation->items->sum('subtotal'), 0, ',', '.') }}
							</h4>
						</td>
					</tr>
					<tr class="">
						<td colspan="4" class="text-end">
							<h4 class="fw-bold" style="margin: 0;">
								Diskon {{ $quotation->discount_percentage }}%
							</h4>
						</td>
						@php
							$discount = $quotation->items->sum('subtotal') * ($quotation->discount_percentage / 100);
						@endphp
						<td>
							<h4 class="fw-bold text-end" style="margin: 0;">
								{{ number_format($discount, 0, ',', '.') }}
							</h4>
						</td>
					</tr>
					<tr class="">
						<td colspan="4" class="text-end">
							<h4 class="fw-bold" style="margin: 0;">
								Pajak {{ $quotation->tax_percentage }}%
							</h4>
						</td>
						@php
							$tax = $quotation->items->sum('subtotal') * ($quotation->tax_percentage / 100);
						@endphp
						<td>
							<h4 class="fw-bold text-end" style="margin: 0;">
								{{ number_format($tax, 0, ',', '.') }}
							</h4>
						</td>
					</tr>
					<tr class="border-top">
						<td colspan="4" class="text-end">
							<h4 class="fw-bold" style="margin: 0;">
								Total
							</h4>
						</td>
						<td>
							<h4 class="fw-bold text-end" style="margin: 0;">
								Rp {{ number_format($quotation->total_amount, 0, ',', '.') }}
							</h4>
						</td>
					</tr>
				</tbody>
			</table>
			<div>
				<h4 style="margin-bottom: 0;">Catatan:</h4>
				<div>{{ $quotation->note }}</div>
			</div>
		</div>



		<h4 style="margin-bottom: 0;">Metode Pembayaran:</h4>
		<div>Bank Mandiri</div>
		<div>139-00-2884911-9</div>
		<div>PT Tekadkan Mimpi Indonesia</div>
	</div>
</body>

</html>
