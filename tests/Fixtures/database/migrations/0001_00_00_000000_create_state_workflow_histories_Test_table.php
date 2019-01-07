<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateStateWorkflowHistoryTable
 */
class CreateStateWorkflowHistoriesTestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
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
        Schema::dropIfExists('state_workflow_histories');
    }
}
