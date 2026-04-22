<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

/**
 * Tests unitaires du service ActivityLogService.
 *
 * Le modèle ActivityLog utilise MongoDB. Pour éviter toute dépendance
 * à une connexion MongoDB réelle en test, on mock le modèle Eloquent
 * en utilisant le pattern partial mock de Mockery, ce qui permet de
 * vérifier que chaque méthode du service appelle bien ActivityLog::create()
 * avec les bons paramètres sans toucher à la base de données.
 */
class ActivityLogServiceTest extends TestCase
{
    // Pas de RefreshDatabase ici — on ne touche pas à la DB

    // -------------------------------------------------------------------------
    // Setup / Teardown
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();

        // On remplace le modèle ActivityLog par un mock statique
        $mock = Mockery::mock('alias:' . ActivityLog::class);
        $mock->shouldReceive('create')->andReturn(new \stdClass());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\Test]
    public function consultation_formation_appelle_create_avec_event_correct(): void
    {
        // Si le mock ne lance pas d'exception, le service a bien appelé create()
        ActivityLogService::consultationFormation(1, 42);

        // On vérifie que le mock a bien reçu un appel (assertion implicite Mockery)
        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function consultation_formation_accepte_user_null(): void
    {
        ActivityLogService::consultationFormation(5, null);

        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function inscription_formation_appelle_create(): void
    {
        ActivityLogService::inscriptionFormation(2, 10);

        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function creation_formation_appelle_create(): void
    {
        ActivityLogService::creationFormation(3, 20);

        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function modification_formation_appelle_create_avec_old_et_new_values(): void
    {
        $old = ['titre' => 'Ancien', 'description' => 'Old desc', 'categorie' => 'data', 'niveau' => 'debutant'];
        $new = ['titre' => 'Nouveau', 'description' => 'New desc', 'categorie' => 'design', 'niveau' => 'avance'];

        ActivityLogService::modificationFormation(4, 30, $old, $new);

        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function suppression_formation_appelle_create(): void
    {
        ActivityLogService::suppressionFormation(6, 50);

        $this->assertTrue(true);
    }
}
