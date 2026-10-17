<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const KEYS = ['ai_api_key', 'gemini_api_key'];

    public function up(): void
    {
        Schema::table('agency_settings', function (Blueprint $table) {
            $table->string('ai_model')->nullable()->after('ai_api_key');
            // An encrypted key is far longer than the plain one.
            $table->text('ai_api_key')->nullable()->change();
            $table->text('gemini_api_key')->nullable()->change();
        });

        foreach (DB::table('agency_settings')->get() as $row) {
            $update = [];

            foreach (self::KEYS as $column) {
                if (filled($row->{$column}) && ! $this->isEncrypted($row->{$column})) {
                    $update[$column] = Crypt::encryptString($row->{$column});
                }
            }

            if ($update !== []) {
                DB::table('agency_settings')->where('id', $row->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('agency_settings')->get() as $row) {
            $update = [];

            foreach (self::KEYS as $column) {
                if (filled($row->{$column}) && $this->isEncrypted($row->{$column})) {
                    $update[$column] = Crypt::decryptString($row->{$column});
                }
            }

            if ($update !== []) {
                DB::table('agency_settings')->where('id', $row->id)->update($update);
            }
        }

        Schema::table('agency_settings', function (Blueprint $table) {
            $table->dropColumn('ai_model');
        });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
