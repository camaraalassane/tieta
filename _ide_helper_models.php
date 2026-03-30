<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $candidature_id
 * @property int $piece_concour_id
 * @property string $nom_fichier
 * @property string $url_fichier
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Concour|null $concour
 * @property-read \App\Models\PieceConcour $pieceConcour
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidaturePiece newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidaturePiece newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidaturePiece query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidaturePiece whereCandidatureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidaturePiece whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidaturePiece whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidaturePiece whereNomFichier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidaturePiece wherePieceConcourId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidaturePiece whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidaturePiece whereUrlFichier($value)
 */
	class CandidaturePiece extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $intitule
 * @property string $description
 * @property string $organisateur
 * @property string|null $avis
 * @property string $diplome_min
 * @property \Illuminate\Support\Carbon|null $date_limite
 * @property int $age
 * @property string $statut
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $admins
 * @property-read int|null $admins_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\candidature> $candidatures
 * @property-read int|null $candidatures_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PieceConcour> $piecesComplementaires
 * @property-read int|null $pieces_complementaires_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\resultat> $resultats
 * @property-read int|null $resultats_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Concour newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Concour newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Concour query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Concour whereAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Concour whereAvis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Concour whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Concour whereDateLimite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Concour whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Concour whereDiplomeMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Concour whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Concour whereIntitule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Concour whereOrganisateur($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Concour whereStatut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Concour whereUpdatedAt($value)
 */
	class Concour extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $concour_id
 * @property string $role
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConcourAdmin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConcourAdmin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConcourAdmin query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConcourAdmin whereConcourId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConcourAdmin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConcourAdmin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConcourAdmin whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConcourAdmin whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConcourAdmin whereUserId($value)
 */
	class ConcourAdmin extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $emetteur_id
 * @property int $destinataire_id
 * @property string $objet
 * @property string $texte
 * @property int $lu
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $concour_id
 * @property-read \App\Models\Concour|null $concour
 * @property-read \App\Models\User|null $destinataire
 * @property-read \App\Models\User|null $emetteur
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereConcourId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereDestinataireId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereEmetteurId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereLu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereObjet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereTexte($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereUpdatedAt($value)
 */
	class Message extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereGuardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission withoutRole($roles, $guard = null)
 */
	class Permission extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $concour_id
 * @property string $nom_document
 * @property string $slug
 * @property int $is_required
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Concour $concour
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PieceConcour newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PieceConcour newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PieceConcour query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PieceConcour whereConcourId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PieceConcour whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PieceConcour whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PieceConcour whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PieceConcour whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PieceConcour whereNomDocument($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PieceConcour whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PieceConcour whereUpdatedAt($value)
 */
	class PieceConcour extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereGuardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role withoutPermission($permissions)
 */
	class Role extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property mixed $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Concour> $concoursGeres
 * @property-read int|null $concours_geres_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 * @property string|null $prenom
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\candidature> $candidatures
 * @property-read int|null $candidatures_count
 * @property-read \App\Models\profil|null $profil
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePrenom($value)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $num_dossier
 * @property string $demande_lettre
 * @property string $nationalite
 * @property int $profil_id
 * @property int $concour_id
 * @property string|null $resultat
 * @property string|null $motif
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Concour $concour
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CandidaturePiece> $piecesChargees
 * @property-read int|null $pieces_chargees_count
 * @property-read \App\Models\profil|null $profil
 * @method static \Illuminate\Database\Eloquent\Builder<static>|candidature newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|candidature newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|candidature query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|candidature whereConcourId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|candidature whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|candidature whereDemandeLettre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|candidature whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|candidature whereMotif($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|candidature whereNationalite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|candidature whereNumDossier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|candidature whereProfilId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|candidature whereResultat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|candidature whereUpdatedAt($value)
 */
	class candidature extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string|null $nom
 * @property string|null $prenom
 * @property string|null $date_naissance
 * @property string|null $lieu_naissance
 * @property string|null $email
 * @property int|null $telephone
 * @property string|null $region
 * @property string|null $carte_identite
 * @property string|null $photo_identite
 * @property string|null $DEF
 * @property string|null $BAC
 * @property string|null $DUT
 * @property string|null $Licence
 * @property string|null $Master
 * @property string|null $Doctorat
 * @property string|null $permis
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $BT
 * @property string|null $CAP
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereBAC($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereBT($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereCAP($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereCarteIdentite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereDEF($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereDUT($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereDateNaissance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereDoctorat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereLicence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereLieuNaissance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereMaster($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereNom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil wherePermis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil wherePhotoIdentite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil wherePrenom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereTelephone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|profil whereUserId($value)
 */
	class profil extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $intitule
 * @property string|null $fichier
 * @property string $statut
 * @property int|null $nombre_candidat
 * @property int $concour_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Concour $concour
 * @method static \Illuminate\Database\Eloquent\Builder<static>|resultat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|resultat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|resultat query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|resultat whereConcourId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|resultat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|resultat whereFichier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|resultat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|resultat whereIntitule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|resultat whereNombreCandidat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|resultat whereStatut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|resultat whereUpdatedAt($value)
 */
	class resultat extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $id_concours
 * @property int $id_profil
 * @property int $id_candidature
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|traitement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|traitement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|traitement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|traitement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|traitement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|traitement whereIdCandidature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|traitement whereIdConcours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|traitement whereIdProfil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|traitement whereUpdatedAt($value)
 */
	class traitement extends \Eloquent {}
}

