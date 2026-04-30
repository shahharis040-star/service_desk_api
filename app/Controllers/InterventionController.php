<?php

// Gestisce richieste HTTP per gli endpoints /interventions
// Tutti i route sono protetti, AuthMiddleware invocato ogni volta


declare(strict_types=1);

class InterventionController
{
    private InterventionService $service;

    public function __construct()
    {
        $this->service = new InterventionService();
    }

    //  GET /interventions
    public function index(array $params = []): void
    {
        $filters = [
            'status'  => $_GET['status']  ?? null,
            'user_id' => $_GET['user_id'] ?? null,
            'search'  => $_GET['search']  ?? null,
            'sort'    => $_GET['sort']    ?? 'created_at',
            'order'   => $_GET['order']   ?? 'desc',
            'page'    => $_GET['page']    ?? 1,
            'limit'   => $_GET['limit']   ?? 20,
        ];

        // Rimuovo filtri nulli
        $filters = array_filter($filters, fn($v) => $v !== null);

        try {
            $result = $this->service->getAll($filters);
            $this->respond(200, $result);
        } catch (InvalidArgumentException $e) {
            $this->respond(400, ['error' => $e->getMessage()]);
        }
    }

    //  POST /interventions
    public function store(array $params = []): void
    {
        $body   = $this->getJsonBody();
        $userId = (int) ($_REQUEST['auth_user_id'] ?? 0);

        try {
            $intervention = $this->service->create($body, $userId);
            $this->respond(201, $intervention);
        } catch (InvalidArgumentException $e) {
            $this->respond(400, ['error' => $e->getMessage()]);
        }
    }

    //  GET /interventions/{id}
    public function show(array $params = []): void
    {
        $id = $this->resolveId($params);

        try {
            $intervention = $this->service->getById($id);
            $this->respond(200, $intervention);
        } catch (RuntimeException $e) {
            $this->respond(404, ['error' => $e->getMessage()]);
        }
    }

    //  PUT /interventions/{id}
    public function update(array $params = []): void
    {
        $id   = $this->resolveId($params);
        $body = $this->getJsonBody();

        try {
            $intervention = $this->service->update($id, $body);
            $this->respond(200, $intervention);
        } catch (InvalidArgumentException $e) {
            $this->respond(400, ['error' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            $this->respond(404, ['error' => $e->getMessage()]);
        }
    }

    //  DELETE /interventions/{id}
    public function destroy(array $params = []): void
    {
        $id = $this->resolveId($params);

        try {
            $this->service->delete($id);
            $this->respond(200, ['message' => 'Intervento eliminato correttamente.']);
        } catch (RuntimeException $e) {
            $this->respond(404, ['error' => $e->getMessage()]);
        }
    }

    private function resolveId(array $params): int
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->respond(400, ['error' => 'ID intervento invalido.']);
            exit;
        }

        return $id;
    }

    private function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }

    private function respond(int $status, array $data): void
    {
        http_response_code($status);
        echo json_encode($data);
    }
}