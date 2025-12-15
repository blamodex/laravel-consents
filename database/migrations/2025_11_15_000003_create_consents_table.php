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
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->index();

            $table->foreignId('consent_type_id')->constrained('consent_types');
            $table->foreignId('consent_source_id')->constrained('consent_sources');

            // Polymorphic relationship
            $table->morphs('consentable');

            // Polymorphic relationship for transferable entity (nullable until transfer occurs)
            $table->nullableMorphs('transferable');

            $table->text('consent_text')->nullable();
            $table->string('status')->default('consented');

            $table->timestamp('transferred_at')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
