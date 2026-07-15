<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appearance_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_id')->constrained()->onDelete('cascade');
            $table->string('area');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->date('appearance_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('comment')->nullable();
            $table->string('nickname', 30)->default('匿名');
            $table->string('ip_hash', 64);
            $table->timestamps();

            $table->index(['truck_id', 'appearance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appearance_slots');
    }
};
