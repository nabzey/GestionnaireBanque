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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->enum('type', ['depot', 'retrait', 'virement', 'transfert'])->default('depot');
            $table->decimal('montant', 15, 2);
            $table->string('devise')->default('FCFA');
            $table->text('description')->nullable();
            $table->enum('statut', ['en_attente', 'validee', 'rejete', 'annulee'])->default('en_attente');
            $table->timestamp('date_execution')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->uuid('compte_id');
            $table->foreign('compte_id')->references('id')->on('comptes')->onDelete('cascade');
            $table->timestamps();

            $table->index(['compte_id', 'type']);
            $table->index('reference');
            $table->index('statut');
            $table->index('date_execution');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
