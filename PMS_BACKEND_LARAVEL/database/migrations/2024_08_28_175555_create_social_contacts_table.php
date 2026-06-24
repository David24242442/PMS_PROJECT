<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('social_contacts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('emp_id')->references('id')->on('employees');
            $table->longText('churchmemberloc')->nullable();
            $table->string('pastor')->nullable();
            $table->integer('memduration')->nullable();
            $table->string('mobileno')->nullable();
            $table->string('ghcardno')->nullable();
            $table->string('closestfriend')->nullable();
            $table->string('contactsphone')->nullable();
            $table->string('workaddress')->nullable();
            $table->boolean('livestatus')->default(true);
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
        Schema::dropIfExists('social_contacts');
    }
}
