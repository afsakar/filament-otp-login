<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if ((! Schema::hasColumn('otp_codes', 'email')) || Schema::hasColumn('otp_codes', 'identifier')) {
            return;
        }

        Schema::table('otp_codes', function (Blueprint $table) {
            $table->renameColumn('email', 'identifier');
        });
    }
};
