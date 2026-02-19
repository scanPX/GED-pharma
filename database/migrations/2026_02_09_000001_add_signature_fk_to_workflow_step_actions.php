<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ged_workflow_step_actions', function (Blueprint $table) {
            $table->foreign('signature_id')
                ->references('id')
                ->on('ged_electronic_signatures')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('ged_workflow_step_actions', function (Blueprint $table) {
            $table->dropForeign(['signature_id']);
        });
    }
};
