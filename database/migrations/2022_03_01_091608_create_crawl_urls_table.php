<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrawlUrlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crawl_urls', function (Blueprint $table) {
            $table->id();

            $table->string('site')->index();
            $table->text('url');

            $table->tinyInteger('data_status')->default(0)->index();
            $table->json('data')->nullable();

            $table->integer('status')->default(0)->index();

            $table->integer('visited')->default(0);

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
        Schema::dropIfExists('crawl_urls');
    }
}
