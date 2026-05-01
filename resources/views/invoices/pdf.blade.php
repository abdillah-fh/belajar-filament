<!DOCTYPE html>
<html lang="id">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>INV-0000{{ $invoice->id }} - {{ $invoice->client_name }} - {{ $invoice->company }} - {{ $invoice->status == 'Unpaid' ? 'Tagihan' : 'Lunas' }}</title>
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
			width: 25%;
			float: left;
			box-sizing: border-box;
			padding-left: 15px;
			height: 100px;
			background-color: #f2ffee;
		}

		.col-7 {
			width: 50%;
			float: left;
			box-sizing: border-box;
			height: 100px;
			background-color: #f2ffee;
		}

		.col-3 {
			width: 20%;
			float: left;
			box-sizing: border-box;
			height: 100px;
		}
	</style>
</head>

<body>
	<div class="container">
		<!-- Header -->
		<img src="{{ public_path('img/Header-Surat.png') }}" width="100%" alt="" style="margin-bottom: 40px;">


		<div class="row">
			<div class="col-1">
				<p style="margin-bottom: -8px;" class="invoice-title">INVOICE #0000{{ $invoice->id }}</p>
				<p style="margin-bottom: -8px;">Tanggal Invoice</p>
				<p>Tanggal Jatuh Tempo </p>
			</div>
			<div class="col-7">
				<p style="margin-bottom: -8px; color: #f2ffee;" class="invoice-title">INVOICE #0000{{ $invoice->id }}</p>
				<p style="margin-bottom: -8px;">: {{ \Carbon\Carbon::parse($invoice->invoice_date)->translatedFormat('d F Y') }}</p>
				<p>: {{ \Carbon\Carbon::parse($invoice->due_date)->translatedFormat('d F Y') }}</p>
			</div>
			<div class="status col-3 {{ $invoice->status == 'unpaid' ? 'bg-danger' : 'bg-success' }}">
				<h1>{{ Str::upper($invoice->status) }}</h1>
			</div>
			<div style="clear: both;"></div>
		</div>

		<div>
			<h4 style="margin-bottom: 0;">Kepada:</h4>
			<div>{{ $invoice->client_name }}</div>
			<div>{{ $invoice->company }}</div>
			<div>{{ $invoice->client_address }}</div>
			<div>{{ $invoice->client_city }}</div>
			<div>{{ $invoice->client_country }}</div>
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
					@foreach ($invoice->items as $product)
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
								{{ number_format($invoice->items->sum('subtotal'), 0, ',', '.') }}
							</h4>
						</td>
					</tr>
					<tr class="">
						<td colspan="4" class="text-end">
							<h4 class="fw-bold" style="margin: 0;">
								Diskon {{ $invoice->discount_percentage }}%
							</h4>
						</td>
						@php
							$discount = $invoice->items->sum('subtotal') * ($invoice->discount_percentage / 100);
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
								Pajak {{ $invoice->tax_percentage }}%
							</h4>
						</td>
						@php
							$tax = $invoice->items->sum('subtotal') * ($invoice->tax_percentage / 100);
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
								Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
							</h4>
						</td>
					</tr>
				</tbody>
			</table>
			<div>
				<h4 style="margin-bottom: 0;">Catatan:</h4>
				<div>{{ $invoice->note }}</div>
			</div>
		</div>



		<h4 style="margin-bottom: 0;">Metode Pembayaran:</h4>
		<div>Bank Mandiri</div>
		<div>139-00-2884911-9</div>
		<div>PT Tekadkan Mimpi Indonesia</div>
	</div>
</body>

</html>
