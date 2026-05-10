<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ClientCrudTest
 *
 * Pruebas de integración que cubren todos los endpoints del CRUD de clientes.
 * Se ejecutan contra una base de datos en memoria (SQLite) gracias a RefreshDatabase.
 *
 * Ejecutar: docker compose exec app php artisan test --filter ClientCrudTest
 */
class ClientCrudTest extends TestCase
{
    use RefreshDatabase;

    // ── Datos de prueba reutilizables ────────────────────────────────────────

    /** @return array<string, mixed> */
    private function validClientData(array $overrides = []): array
    {
        return array_merge([
            'first_name'    => 'Juan',
            'last_name'     => 'Pérez',
            'email'         => 'juan.perez@fintech.test',
            'phone_number'  => '+56912345678',
            'date_of_birth' => '1995-05-15',
        ], $overrides);
    }

    // ── CE1 / CE2: Index ─────────────────────────────────────────────────────

    /** GET /api/v1/clients → 200 con array vacío inicial */
    public function test_index_returns_empty_list_initially(): void
    {
        $response = $this->getJson('/api/v1/clients');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'count'   => 0,
            ]);
    }

    /** GET /api/v1/clients → 200 con clientes creados */
    public function test_index_returns_all_clients(): void
    {
        Client::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/clients');

        $response->assertOk()
            ->assertJson(['success' => true, 'count' => 3]);
    }

    // ── CE2: Store ───────────────────────────────────────────────────────────

    /** POST /api/v1/clients → 201 cliente creado */
    public function test_store_creates_client_and_returns_201(): void
    {
        $response = $this->postJson('/api/v1/clients', $this->validClientData());

        $response->assertCreated()
            ->assertJsonStructure([
                'success', 'message',
                'data' => ['client_id', 'first_name', 'last_name', 'email'],
            ]);

        $this->assertDatabaseHas('clients', ['email' => 'juan.perez@fintech.test']);
    }

    /** POST /api/v1/clients con email duplicado → 422 */
    public function test_store_fails_with_duplicate_email(): void
    {
        Client::factory()->create(['email' => 'juan.perez@fintech.test']);

        $response = $this->postJson('/api/v1/clients', $this->validClientData());

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email']]);
    }

    /** POST /api/v1/clients con cliente menor de edad → 422 */
    public function test_store_fails_with_underage_client(): void
    {
        $data = $this->validClientData(['date_of_birth' => '2015-01-01']);

        $response = $this->postJson('/api/v1/clients', $data);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    /** POST /api/v1/clients con email inválido → 422 */
    public function test_store_fails_with_invalid_email(): void
    {
        $data = $this->validClientData(['email' => 'email-invalido']);

        $response = $this->postJson('/api/v1/clients', $data);

        $response->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['email']]);
    }

    /** POST /api/v1/clients sin campos obligatorios → 422 */
    public function test_store_fails_when_required_fields_missing(): void
    {
        $response = $this->postJson('/api/v1/clients', [
            'first_name' => 'Solo',
            'last_name'  => 'Nombre',
        ]);

        $response->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['email', 'phone_number', 'date_of_birth']]);
    }

    // ── CE2: Show ────────────────────────────────────────────────────────────

    /** GET /api/v1/clients/{id} → 200 cliente encontrado */
    public function test_show_returns_specific_client(): void
    {
        $client = Client::factory()->create();

        $response = $this->getJson("/api/v1/clients/{$client->client_id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.client_id', $client->client_id);
    }

    /** GET /api/v1/clients/99999 → 404 */
    public function test_show_returns_404_for_nonexistent_client(): void
    {
        $response = $this->getJson('/api/v1/clients/99999');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    // ── CE2 / CE4: Update ───────────────────────────────────────────────────

    /** PUT /api/v1/clients/{id} → 200 actualizado */
    public function test_update_modifies_client_fields(): void
    {
        $client = Client::factory()->create();

        $response = $this->putJson("/api/v1/clients/{$client->client_id}", [
            'phone_number' => '+56999999999',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.phone_number', '+56999999999');
    }

    /** PUT /api/v1/clients/99999 → 404 */
    public function test_update_returns_404_for_nonexistent_client(): void
    {
        $response = $this->putJson('/api/v1/clients/99999', ['first_name' => 'Test']);

        $response->assertNotFound();
    }

    // ── CE2 / CE4: Destroy ──────────────────────────────────────────────────

    /** DELETE /api/v1/clients/{id} → 200 eliminado y ausente en BD */
    public function test_destroy_removes_client_from_database(): void
    {
        $client = Client::factory()->create();

        $response = $this->deleteJson("/api/v1/clients/{$client->client_id}");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('clients', ['client_id' => $client->client_id]);
    }

    /** DELETE /api/v1/clients/99999 → 404 */
    public function test_destroy_returns_404_for_nonexistent_client(): void
    {
        $response = $this->deleteJson('/api/v1/clients/99999');

        $response->assertNotFound();
    }

    // ── CE2: Search ─────────────────────────────────────────────────────────

    /** GET /api/v1/clients/search?q=juan → 200 con resultados */
    public function test_search_returns_matching_clients(): void
    {
        Client::factory()->create(['first_name' => 'Juan', 'email' => 'juan@test.com']);
        Client::factory()->create(['first_name' => 'María', 'email' => 'maria@test.com']);

        $response = $this->getJson('/api/v1/clients/search?q=juan');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 1);
    }

    /** GET /api/v1/clients/search sin parámetro → 400 */
    public function test_search_returns_400_without_query_param(): void
    {
        $response = $this->getJson('/api/v1/clients/search');

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }
}
