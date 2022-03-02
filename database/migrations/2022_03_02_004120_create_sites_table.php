<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();

            $table->string('site')->index();
            $table->text('url');

            $table->string('filter_parent')->nullable();
            $table->string('filter_title')->nullable();
            $table->string('filter_description')->nullable();
            $table->string('filter_link')->nullable();
            $table->string('filter_view')->nullable();

            $table->integer('status')->default(0)->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sites');
    }
}
