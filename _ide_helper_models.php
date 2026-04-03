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
 * @property string $num_dossier
 * @property string $demande_lettre
 * @property int $profil_id
 * @property int $concour_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $nationalite
 * @property string|null $resultat
 * @property string|null $motif
 * @property-read \App\Models\Concour $concour
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CandidaturePiece> $piecesChargees
 * @property-read int|null $pieces_chargees_count
 * @property-read \App\Models\Profil|null $profil
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidature newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidature newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidature query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidature whereConcourId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidature whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidature whereDemandeLettre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidature whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidature whereMotif($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidature whereNationalite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidature whereNumDossier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidature whereProfilId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidature whereResultat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidature whereUpdatedAt($value)
 */
	class Candidature extends \Eloquent {}
}

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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $statut
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $admins
 * @property-read int|null $admins_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Candidature> $candidatures
 * @property-read int|null $candidatures_count
 * @property-read mixed $date_formatee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PieceConcour> $piecesComplementaires
 * @property-read int|null $pieces_complementaires_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Resultat> $resultats
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
 * @property bool $lu
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $concour_id
 * @property-read \App\Models\Concour $concour
 * @property-read \App\Models\User $destinataire
 * @property-read \App\Models\User $emetteur
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
 * @property bool $is_required
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
 * @property string|null $nom
 * @property string|null $prenom
 * @property string|null $email
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
 * @property int|null $telephone
 * @property int $user_id
 * @property string|null $BT
 * @property string|null $CAP
 * @property string|null $date_naissance
 * @property string|null $lieu_naissance
 * @property string|null $sexe
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereBAC($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereBT($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereCAP($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereCarteIdentite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereDEF($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereDUT($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereDateNaissance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereDoctorat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereLicence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereLieuNaissance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereMaster($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereNom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil wherePermis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil wherePhotoIdentite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil wherePrenom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereSexe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereTelephone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profil whereUserId($value)
 */
	class Profil extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $intitule
 * @property string|null $fichier
 * @property int|null $nombre_candidat
 * @property int $concour_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $statut
 * @property-read \App\Models\Concour $concour
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultat query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultat whereConcourId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultat whereFichier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultat whereIntitule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultat whereNombreCandidat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultat whereStatut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultat whereUpdatedAt($value)
 */
	class Resultat extends \Eloquent {}
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
 * @property int $id_concours
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $communique
 * @property string|null $communique_titre
 * @property \Illuminate\Support\Carbon|null $communique_published_at
 * @property bool $communique_is_active
 * @property \Illuminate\Support\Carbon|null $date_limite
 * @property-read \App\Models\Concour|null $concour
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traitement activeCommuniques()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traitement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traitement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traitement notExpired()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traitement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traitement whereCommunique($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traitement whereCommuniqueIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traitement whereCommuniquePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traitement whereCommuniqueTitre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traitement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traitement whereDateLimite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traitement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traitement whereIdConcours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Traitement whereUpdatedAt($value)
 */
	class Traitement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $prenom
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Candidature> $candidatures
 * @property-read int|null $candidatures_count
 * @property-read \App\Models\Profil|null $profil
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePrenom($value)
 */
	class User extends \Eloquent {}
}

