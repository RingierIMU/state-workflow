<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateStateWorkflowHistoryTable.
 */
return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('state_workflow_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('model_name')->index();
            $table->integer('model_id')->index();
            $table->string('transition');
            $table->string('from');
            $table->string('to');
            $table->integer('user_id')->index();
            $table->json('context');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('state_workflow_histories');
    }
};
