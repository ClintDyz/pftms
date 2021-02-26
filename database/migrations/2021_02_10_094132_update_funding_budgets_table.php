<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFundingBudgetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('funding_budgets', function (Blueprint $table) {
            $table->uuid('created_by')->nullable()->after('is_active');
            $table->foreign('created_by')->references('id')->on('emp_accounts');
            $table->uuid('prepared_by')->nullable()->after('created_by');
            $table->foreign('prepared_by')->nullable()->references('id')->on('emp_accounts');
            $table->uuid('recommended_by')->nullable()->after('prepared_by');
            $table->foreign('recommended_by')->nullable()->references('id')->on('emp_accounts');
            $table->uuid('certified_funds_available_by')->nullable()->after('recommended_by');
            $table->foreign('certified_funds_available_by')->nullable()->references('id')->on('emp_accounts');
            $table->uuid('approved_by')->nullable()->after('certified_funds_available_by');
            $table->foreign('approved_by')->references('id')->on('emp_accounts');
            $table->uuid('disapproved_by')->nullable()->after('approved_by');
            $table->foreign('disapproved_by')->references('id')->on('emp_accounts');
            $table->dateTime('date_approved')->nullable()->after('project_id');
            $table->dateTime('date_disapproved')->nullable()->after('project_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('funding_budgets', function (Blueprint $table) {
           $table->dropForeign('funding_budgets_created_by_foreign');
           $table->dropForeign('funding_budgets_approved_by_foreign');
           $table->dropForeign('funding_budgets_prepared_by_foreign');
           $table->dropForeign('funding_budgets_recommended_by_foreign');
           $table->dropForeign('funding_budgets_certified_funds_available_by_foreign');
           $table->dropForeign('funding_budgets_disapproved_by_foreign');
           $table->dropColumn('created_by');
           $table->dropColumn('approved_by');
           $table->dropColumn('prepared_by');
           $table->dropColumn('recommended_by');
           $table->dropColumn('certified_funds_available_by');
           $table->dropColumn('disapproved_by');
           $table->dropColumn('date_approved');
           $table->dropColumn('date_disapproved');
        });
    }
}
