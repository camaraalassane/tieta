<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('
            -- Fonction qui sera appelée par le trigger
            CREATE OR REPLACE FUNCTION auto_close_expired_concours()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Si la date_limite est dépassée ET le statut est encore "Actif"
                IF NEW.date_limite < NOW() AND NEW.statut = \'Actif\' THEN
                    NEW.statut := \'Inactif\';
                    NEW.updated_at := NOW();
                END IF;
                
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');

        DB::unprepared('
            -- Trigger qui se déclenche avant chaque UPDATE sur la table concours
            CREATE TRIGGER trigger_auto_close_expired_concours
            BEFORE UPDATE ON concours
            FOR EACH ROW
            EXECUTE FUNCTION auto_close_expired_concours();
        ');

        DB::unprepared('
            -- Trigger qui se déclenche avant chaque INSERT (optionnel)
            CREATE TRIGGER trigger_auto_close_expired_concours_insert
            BEFORE INSERT ON concours
            FOR EACH ROW
            EXECUTE FUNCTION auto_close_expired_concours();
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_auto_close_expired_concours ON concours;');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_auto_close_expired_concours_insert ON concours;');
        DB::unprepared('DROP FUNCTION IF EXISTS auto_close_expired_concours();');
    }
};
