<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PassportPolymorphicChanges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->string('user_type')->nullable()->after('user_id');
        });

        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->string('user_type')->index()->nullable()->after('user_id');
        });

        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('user_type')->index()->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->dropColumn('user_type');
        });

        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->dropColumn('user_type');
        });

        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->dropColumn('user_type');
        });
    }
}
