<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasTable('users') && Schema::hasTable('posts')) {
            Schema::create('likes', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user')->unsigned();
                $table->foreign('user')->references('id')->on('users');
                $table->bigInteger('post')->unsigned();
                $table->foreign('post')->references('id')->on('posts');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('likes');
    }
};
