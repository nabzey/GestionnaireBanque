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
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero')->unique();
            $table->decimal('solde_initial', 15, 2)->default(0);
            $table->string('devise')->default('FCFA');
            $table->string('type')->default('cheque'); // cheque, courant, épargne, etc.
            $table->enum('statut', ['actif', 'bloque', 'ferme'])->default('actif');
            $table->text('motif_blocage')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->foreignId('admin_id')->constrained('admins')->onDelete('cascade');
            $table->timestamps();

            $table->index(['admin_id', 'type']);
            $table->index('numero');
            $table->index('devise');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
