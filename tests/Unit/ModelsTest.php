<?php

namespace Tests\Unit;

use App\Models\Formation;
use App\Models\FormationVue;
use App\Models\Inscription;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitaires des modèles Eloquent.
 * Vérifie les relations, les fillable, les casts et les méthodes JWT.
 */
class ModelsTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeFormateur(string $suffix = ''): User
    {
        return User::create([
            'nom'      => 'Formateur' . $suffix,
            'email'    => 'formateur' . $suffix . uniqid() . '@test.com',
            'password' => bcrypt('secret123'),
            'role'     => 'formateur',
        ]);
    }

    private function makeApprenant(string $suffix = ''): User
    {
        return User::create([
            'nom'      => 'Apprenant' . $suffix,
            'email'    => 'apprenant' . $suffix . uniqid() . '@test.com',
            'password' => bcrypt('secret123'),
            'role'     => 'apprenant',
        ]);
    }

    private function makeFormation(User $formateur, array $overrides = []): Formation
    {
        return Formation::create(array_merge([
            'titre'          => 'Formation Test',
            'description'    => 'Description test',
            'categorie'      => 'developpement_web',
            'niveau'         => 'debutant',
            'nombre_de_vues' => 0,
            'formateur_id'   => $formateur->id,
        ], $overrides));
    }

    private function makeModule(Formation $formation, int $ordre = 1): Module
    {
        return Module::create([
            'titre'        => 'Module ' . $ordre,
            'contenu'      => 'Contenu du module ' . $ordre,
            'ordre'        => $ordre,
            'formation_id' => $formation->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Tests User
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_peut_etre_cree_avec_role_formateur(): void
    {
        $user = $this->makeFormateur('_A');

        $this->assertDatabaseHas('users', ['email' => $user->email]);
        $this->assertEquals('formateur', $user->role);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_peut_etre_cree_avec_role_apprenant(): void
    {
        $user = $this->makeApprenant('_B');

        $this->assertDatabaseHas('users', ['email' => $user->email]);
        $this->assertEquals('apprenant', $user->role);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_mot_de_passe_est_hache(): void
    {
        $user = $this->makeApprenant('_hash');

        $this->assertNotEquals('secret123', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('secret123', $user->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_jwt_identifier_retourne_la_cle_primaire(): void
    {
        $user = $this->makeApprenant('_jwt');

        $this->assertEquals($user->id, $user->getJWTIdentifier());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_jwt_custom_claims_est_tableau_vide(): void
    {
        $user = $this->makeApprenant('_claims');

        $this->assertIsArray($user->getJWTCustomClaims());
        $this->assertEmpty($user->getJWTCustomClaims());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_password_est_hidden(): void
    {
        $user  = $this->makeApprenant('_hidden');
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_formateur_a_des_formations(): void
    {
        $formateur = $this->makeFormateur('_rel');
        $this->makeFormation($formateur);
        $this->makeFormation($formateur, ['titre' => 'Formation 2']);

        $this->assertCount(2, $formateur->formations);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_apprenant_a_des_inscriptions(): void
    {
        $formateur = $this->makeFormateur('_ins');
        $apprenant = $this->makeApprenant('_ins');
        $formation = $this->makeFormation($formateur);

        Inscription::create([
            'utilisateur_id' => $apprenant->id,
            'formation_id'   => $formation->id,
            'progression'    => 0,
        ]);

        $this->assertCount(1, $apprenant->inscriptions);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_peut_avoir_des_modules_termines(): void
    {
        $formateur = $this->makeFormateur('_mod');
        $apprenant = $this->makeApprenant('_mod');
        $formation = $this->makeFormation($formateur);
        $module    = $this->makeModule($formation);

        $apprenant->modulesTermines()->attach($module->id, ['termine' => true]);

        $this->assertCount(1, $apprenant->modulesTermines);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_a_des_messages_envoyes_et_recus(): void
    {
        $expediteur   = $this->makeApprenant('_exp');
        $destinataire = $this->makeFormateur('_dest');

        \App\Models\Message::create([
            'expediteur_id'   => $expediteur->id,
            'destinataire_id' => $destinataire->id,
            'contenu'         => 'Bonjour',
            'lu'              => false,
        ]);

        $this->assertCount(1, $expediteur->messagesEnvoyes);
        $this->assertCount(1, $destinataire->messagesRecus);
    }

    // -------------------------------------------------------------------------
    // Tests Formation
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\Test]
    public function formation_appartient_a_un_formateur(): void
    {
        $formateur = $this->makeFormateur('_f1');
        $formation = $this->makeFormation($formateur);

        $this->assertEquals($formateur->id, $formation->formateur->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function formation_a_des_modules(): void
    {
        $formateur = $this->makeFormateur('_f2');
        $formation = $this->makeFormation($formateur);
        $this->makeModule($formation, 1);
        $this->makeModule($formation, 2);

        $this->assertCount(2, $formation->modules);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function formation_a_des_inscriptions(): void
    {
        $formateur = $this->makeFormateur('_f3');
        $apprenant = $this->makeApprenant('_f3');
        $formation = $this->makeFormation($formateur);

        Inscription::create([
            'utilisateur_id' => $apprenant->id,
            'formation_id'   => $formation->id,
            'progression'    => 0,
        ]);

        $this->assertCount(1, $formation->inscriptions);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function formation_a_des_vues(): void
    {
        $formateur = $this->makeFormateur('_f4');
        $apprenant = $this->makeApprenant('_f4');
        $formation = $this->makeFormation($formateur);

        FormationVue::create([
            'formation_id'   => $formation->id,
            'utilisateur_id' => $apprenant->id,
            'ip'             => '127.0.0.1',
        ]);

        $this->assertCount(1, $formation->vues);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function formation_fillable_contient_les_champs_requis(): void
    {
        $formation = new Formation();
        $fillable  = $formation->getFillable();

        $this->assertContains('titre', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('categorie', $fillable);
        $this->assertContains('niveau', $fillable);
        $this->assertContains('formateur_id', $fillable);
    }

    // -------------------------------------------------------------------------
    // Tests Module
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\Test]
    public function module_appartient_a_une_formation(): void
    {
        $formateur = $this->makeFormateur('_m1');
        $formation = $this->makeFormation($formateur);
        $module    = $this->makeModule($formation);

        $this->assertEquals($formation->id, $module->formation->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function module_a_des_utilisateurs_via_pivot(): void
    {
        $formateur = $this->makeFormateur('_m2');
        $apprenant = $this->makeApprenant('_m2');
        $formation = $this->makeFormation($formateur);
        $module    = $this->makeModule($formation);

        $apprenant->modulesTermines()->attach($module->id, ['termine' => true]);

        $this->assertCount(1, $module->utilisateurs);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function module_fillable_contient_les_champs_requis(): void
    {
        $module   = new Module();
        $fillable = $module->getFillable();

        $this->assertContains('titre', $fillable);
        $this->assertContains('contenu', $fillable);
        $this->assertContains('ordre', $fillable);
        $this->assertContains('formation_id', $fillable);
    }

    // -------------------------------------------------------------------------
    // Tests Inscription
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\Test]
    public function inscription_appartient_a_un_utilisateur_et_une_formation(): void
    {
        $formateur   = $this->makeFormateur('_i1');
        $apprenant   = $this->makeApprenant('_i1');
        $formation   = $this->makeFormation($formateur);

        $inscription = Inscription::create([
            'utilisateur_id' => $apprenant->id,
            'formation_id'   => $formation->id,
            'progression'    => 50,
        ]);

        $this->assertEquals($apprenant->id, $inscription->utilisateur->id);
        $this->assertEquals($formation->id, $inscription->formation->id);
        $this->assertEquals(50, $inscription->progression);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function inscription_fillable_contient_les_champs_requis(): void
    {
        $inscription = new Inscription();
        $fillable    = $inscription->getFillable();

        $this->assertContains('utilisateur_id', $fillable);
        $this->assertContains('formation_id', $fillable);
        $this->assertContains('progression', $fillable);
    }
}
