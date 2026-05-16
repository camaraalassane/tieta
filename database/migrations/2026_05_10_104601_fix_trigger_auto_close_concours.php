<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer les anciens triggers
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_auto_close_expired_concours ON concours;');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_auto_close_expired_concours_insert ON concours;');
        DB::unprepared('DROP FUNCTION IF EXISTS auto_close_expired_concours();');

        // Creer la nouvelle fonction (Inactif SEULEMENT le lendemain)
        DB::unprepared("
            CREATE OR REPLACE FUNCTION auto_close_expired_concours()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF NEW.date_limite + INTERVAL '1 day' <= CURRENT_DATE AND NEW.statut = 'Actif' THEN
                    NEW.statut := 'Inactif';
                    NEW.updated_at := NOW();
                END IF;
                
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // Trigger UPDATE
        DB::unprepared("
            CREATE TRIGGER trigger_auto_close_expired_concours
            BEFORE UPDATE ON concours
            FOR EACH ROW
            EXECUTE FUNCTION auto_close_expired_concours();
        ");

        // Trigger INSERT
        DB::unprepared("
            CREATE TRIGGER trigger_auto_close_expired_concours_insert
            BEFORE INSERT ON concours
            FOR EACH ROW
            EXECUTE FUNCTION auto_close_expired_concours();
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_auto_close_expired_concours ON concours;');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_auto_close_expired_concours_insert ON concours;');
        DB::unprepared('DROP FUNCTION IF EXISTS auto_close_expired_concours();');
    }
};
