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
        Schema::create('domain_tld_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tld_id')
                ->constrained('domain_tlds')
                ->onDelete('cascade');

            $table->integer('years');
            $table->decimal('register_price', 10, 2);
            $table->decimal('renewal_price', 10, 2);
            $table->timestamps();

            $table->unique(['tld_id', 'years']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_tld_prices');
    }
};
