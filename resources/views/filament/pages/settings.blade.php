<x-filament-panels::page>

	<!-- Gunakan tag form biasa dengan class Tailwind agar jaraknya rapi -->
	<form wire:submit="save" class="space-y-6">

		<!-- Render form schema dari PHP -->
		{{ $this->form }}

		<!-- Tombol Submit -->
		<div class="flex items-center justify-start" style="margin-top:20px">
			<x-filament::button type="submit" size="sm" wire:target="save">
				Simpan Perubahan
			</x-filament::button>
		</div>

	</form>

</x-filament-panels::page>
