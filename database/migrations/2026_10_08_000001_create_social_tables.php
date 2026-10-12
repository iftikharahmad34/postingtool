<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('platform')->unique();
            $table->text('credentials');
            $table->string('account_name')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status')->default('publishing');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('post_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->text('body')->nullable();
            $table->string('status')->default('pending');
            $table->string('external_id')->nullable();
            $table->string('url', 1024)->nullable();
            $table->text('error')->nullable();
            $table->string('note')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_targets');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('social_accounts');
    }
};
