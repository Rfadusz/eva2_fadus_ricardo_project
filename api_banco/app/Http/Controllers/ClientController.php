<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

/**
 * ClientController
 *
 * Controlador RESTful para la gestión de clientes del sistema Fintech Solutions S.A.
 * Expone endpoints CRUD completos con validación de datos y manejo de errores.
 *
 * Rutas registradas (ver routes/api.php):
 *   GET    /api/v1/clients         → index()
 *   POST   /api/v1/clients         → store()
 *   GET    /api/v1/clients/{id}    → show()
 *   PUT    /api/v1/clients/{id}    → update()
 *   DELETE /api/v1/clients/{id}    → destroy()
 *   GET    /api/v1/clients/search  → search()
 */
class ClientController
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/v1/clients
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Listar todos los clientes registrados.
     *
     * @return JsonResponse  200 con array de clientes | 500 si falla BD
     */
    public function index(): JsonResponse
    {
        try {
            $clients = Client::all();

            return response()->json([
                'success' => true,
                'data'    => $clients,
                'count'   => $clients->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de clientes',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/v1/clients
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Registrar un nuevo cliente en la base de datos.
     *
     * Validaciones aplicadas:
     *   - first_name, last_name, email, phone_number, date_of_birth obligatorios
     *   - email debe ser formato válido y único en la tabla clients
     *   - phone_number acepta solo dígitos, +, -, espacios y paréntesis
     *   - date_of_birth: fecha pasada y el cliente debe ser mayor de 18 años
     *
     * @return JsonResponse  201 cliente creado | 422 validación | 500 error BD
     */
    public function store(Request $request): JsonResponse
    {
        try {
                        // Cambia esto en tu función store()
            $validated = $request->validate([
                'rut'      => 'required|string|unique:clientes,rut',
                'nombre'   => 'required|string|max:100',
                'apellido' => 'required|string|max:100',
                'email'    => 'required|email|unique:clientes,email',
                'telefono' => 'required|string|max:20',
            ], [
                // Actualiza también los mensajes de error para que coincidan
                'nombre.required'   => 'El nombre es obligatorio',
                'apellido.required' => 'El apellido es obligatorio',
                'telefono.required' => 'El teléfono es obligatorio',
            ]);

            // Y cuando creas el cliente:
            $client = Client::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Cliente creado exitosamente',
                'data'    => $client,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);

        } catch (QueryException $e) {
            // Captura constraint de unicidad si la validación fue omitida
            if ($e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors'  => ['email' => ['Este email ya está registrado en el sistema']],
                ], 409);
            }
            return response()->json([
                'success' => false,
                'message' => 'Error de base de datos',
                'error'   => $e->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/v1/clients/{id}
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Obtener un cliente específico por su ID.
     *
     * @param  int|string  $id  Identificador del cliente
     * @return JsonResponse  200 cliente | 404 no encontrado | 500 error
     */
    public function show(int|string $id): JsonResponse
    {
        try {
            $client = Client::findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => $client,
            ], 200);

        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado',
                'error'   => "No existe un cliente con ID: {$id}",
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el cliente',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /api/v1/clients/{id}
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Actualizar los datos de un cliente existente.
     *
     * Usa "sometimes" para que cada campo sea opcional en la actualización;
     * solo los campos enviados son validados y actualizados.
     *
     * @param  int|string  $id
     * @return JsonResponse  200 actualizado | 404 | 422 validación | 500
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        try {
            $client = Client::findOrFail($id);

            $validated = $request->validate([
                'first_name'    => 'sometimes|string|max:100',
                'last_name'     => 'sometimes|string|max:100',
                'email'         => "sometimes|email|unique:clients,email,{$id},client_id",
                'phone_number'  => ['sometimes', 'string', 'max:20', 'regex:/^[0-9\+\-\s\(\)]+$/'],
            ], [
                'email.email'          => 'El email debe tener un formato válido',
                'email.unique'         => 'Este email ya está registrado en el sistema',
                'phone_number.regex'   => 'El teléfono tiene un formato inválido',
            ]);

            $client->update($validated);
            $client->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Cliente actualizado exitosamente',
                'data'    => $client,
            ], 200);

        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado',
                'error'   => "No existe un cliente con ID: {$id}",
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el cliente',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE /api/v1/clients/{id}
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Eliminar un cliente de la base de datos.
     *
     * @param  int|string  $id
     * @return JsonResponse  200 eliminado | 404 | 500
     */
    public function destroy(int|string $id): JsonResponse
    {
        try {
            $client     = Client::findOrFail($id);
            $clientName = "{$client->first_name} {$client->last_name}";

            $client->delete();

            return response()->json([
                'success' => true,
                'message' => "Cliente '{$clientName}' eliminado exitosamente",
            ], 200);

        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado',
                'error'   => "No existe un cliente con ID: {$id}",
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el cliente',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/v1/clients/search?q=texto
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Buscar clientes por nombre, apellido o email.
     *
     * Parámetro de query requerido: ?q=término
     *
     * @return JsonResponse  200 resultados | 400 sin parámetro | 500
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q');

            if (empty($query)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe proporcionar un término de búsqueda usando el parámetro "q"',
                ], 400);
            }

            $clients = Client::where('first_name', 'LIKE', "%{$query}%")
                ->orWhere('last_name',  'LIKE', "%{$query}%")
                ->orWhere('email',      'LIKE', "%{$query}%")
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $clients,
                'count'   => $clients->count(),
                'query'   => $query,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
