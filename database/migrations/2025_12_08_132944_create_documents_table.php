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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained(); // Qui a uploadé
            $table->string('name'); // Nom d'affichage (ex: "CNI Monsieur.pdf")
            $table->string('path'); // Chemin stockage (ex: "documents/xyz.pdf")
            $table->string('mime_type')->nullable(); // pdf, jpg, etc.
            $table->integer('size')->nullable(); // en octets
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
