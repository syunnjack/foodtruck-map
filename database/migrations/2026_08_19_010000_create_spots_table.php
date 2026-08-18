<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * キッチンカーが出店する場所（自治体が公認・実施しているスポット）。
     *
     * trucks / appearance_slots は利用者の投稿でたまるデータだが、投稿が集まる前は
     * サイトに何も表示されない。自治体が公表している出店場所は公的に裏が取れて
     * 内容も変わりにくいため、こちらは編集部が用意する固定データとして持つ。
     */
    public function up(): void
    {
        Schema::create('spots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('area');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            // 出店時間。自治体が公表していない場所は null
            $table->string('hours')->nullable();
            $table->text('note')->nullable();
            // 出典。どの自治体のどのページで確認したかを必ず表示する
            $table->string('source_url');
            $table->string('source_label');
            $table->timestamps();

            $table->index('area');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spots');
    }
};
