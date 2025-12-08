<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. UTILISATEURS (Modification de la table default users si besoin, ou création ici)
        // On assume que la table 'users' existe déjà, on ajoute juste le rôle
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'assistant', 'lawyer'])->default('assistant')->after('email');
            $table->string('avatar_path')->nullable()->after('role');
            $table->softDeletes();
        });

        // 2. CLIENTS
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['individual', 'company'])->default('individual');
            $table->string('name'); // Nom complet ou Raison sociale
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable(); // Notes internes globales
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. DOSSIERS
        Schema::create('dossiers', function (Blueprint $table) {
            $table->id();
            $table->string('ref_number')->unique(); // Ex: 2025-001
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            // L'avocat référent (Me Ali par défaut, mais modifiable)
            $table->foreignId('lawyer_id')->nullable()->constrained('users')->nullOnDelete(); 
            
            $table->string('type'); // Litige, Conseil, Divorce...
            $table->enum('status', ['open', 'in_progress', 'waiting', 'closed', 'archived'])->default('open');
            $table->string('subject'); // Objet court
            $table->text('description')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. RÈGLEMENTS (Paiements)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('method'); // Card, Transfer, Cash, Check
            $table->string('reference')->nullable(); // N° Chèque, ID Stripe...
            $table->enum('status', ['paid', 'pending', 'partial'])->default('paid');
            $table->timestamps();
        });

        // 5. RENDEZ-VOUS
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
            
            $table->string('title');
            $table->enum('type', ['legal', 'closing', 'phone', 'other'])->default('legal');
            
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            
            $table->string('location')->nullable(); // Cabinet, Tribunal, Visio
            $table->text('notes')->nullable(); // Notes préparatoires
            
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'postponed'])->default('scheduled');
            
            $table->timestamps();
        });

        // Table pivot pour les participants aux RDV (Toi, Aïcha, Ali)
        Schema::create('appointment_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
        });

        // 6. COMPTES RENDUS (REPORTS)
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete(); // Lié à un RDV spécifique ?
            $table->foreignId('author_id')->constrained('users'); // Qui a écrit ?
            
            $table->enum('type', ['closing', 'legal_meeting', 'phone_call', 'court_hearing']);
            $table->date('report_date');
            
            $table->json('content'); // Stockage structuré (pour le rich text ou blocs)
            $table->enum('status', ['draft', 'finalized'])->default('draft');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('appointment_user');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('dossiers');
        Schema::dropIfExists('clients');
        // Attention au rollback de users column en prod, ici en dev c'est ok
    }
};