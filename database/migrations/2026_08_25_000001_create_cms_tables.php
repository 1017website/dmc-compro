<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('content_key')->unique();
            $table->string('group_name')->nullable();
            $table->string('label')->nullable();
            $table->string('type')->default('text');
            $table->longText('value_id')->nullable();
            $table->longText('value_en')->nullable();
            $table->longText('value_zh')->nullable();
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('need');
            $table->text('message')->nullable();
            $table->enum('status', ['new', 'contacted', 'closed'])->default('new');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('path')->default('/');
            $table->string('visitor_hash', 64)->index();
            $table->text('referrer')->nullable();
            $table->string('country', 2)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('viewed_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('site_contents');
    }
};
