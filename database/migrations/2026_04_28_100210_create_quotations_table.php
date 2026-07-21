<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('sent'); // unpaid, pending, paid
            $table->date('quo_date');

            // Informasi Client saat invoice dibuat (Snapshot)
            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_phone')->nullable();
            $table->string('client_address')->nullable();
            $table->string('client_city')->nullable();
            $table->string('client_country')->nullable();
            $table->string('company')->nullable();

            $table->text('note')->nullable();
            $table->integer('tax_percentage')->default(0);
            $table->integer('discount_percentage')->default(0);


            // Nilai Akhir (Gunakan BigInteger jika tidak pakai desimal/sen)
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount_amount')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('total_pph_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
