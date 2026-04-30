<?php

//  Logica degli interventi

declare(strict_types=1);

class InterventionService
{
    private InterventionRepository $repo;

    private const ALLOWED_STATUSES = ['open', 'in_progress', 'closed'];

    public function __construct()
    {
        $this->repo = new InterventionRepository();
    }

    // Ottieni tutti gli interventi
    public function getAll(array $filters = []): array
    {
        // Controllo se i filtri passati sono validi
        if (!empty($filters['status']) && !in_array($filters['status'], self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException(
                'Invalid status. Allowed values: ' . implode(', ', self::ALLOWED_STATUSES)
            );
        }

        $items = $this->repo->findAll($filters);
        $total = $this->repo->countAll($filters);

        $limit = max(1, min(100, (int) ($filters['limit'] ?? 20)));
        $page  = max(1, (int) ($filters['page'] ?? 1));

        return [
            'data' => $items,
            'meta' => [
                'total'        => $total,
                'page'         => $page,
                'limit'        => $limit,
                'total_pages'  => (int) ceil($total / $limit),
            ],
        ];
    }

    // Ottieni singolo intervento
    public function getById(int $id): array
    {
        $intervention = $this->repo->findById($id);

        if ($intervention === null) {
            throw new RuntimeException('Intervention not found.');
        }

        return $intervention;
    }

    // Crea nuovo intervento
    public function create(array $data, int $userId): array
    {
        // Controlli di validazione
        if (empty($data['title'])) {
            throw new InvalidArgumentException('Inserire titolo.');
        }

        if (strlen(trim($data['title'])) < 3) {
            throw new InvalidArgumentException('Il titolo deve avere almeno 3 caratteri.');
        }

        if (!empty($data['status']) && !in_array($data['status'], self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException(
                'Stato invalido. Valori permessi: ' . implode(', ', self::ALLOWED_STATUSES)
            );
        }

        $id = $this->repo->create([
            'title'       => trim($data['title']),
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'status'      => $data['status'] ?? 'open',
            'user_id'     => $userId,
        ]);

        return $this->repo->findById($id);
    }

    // Aggiorna intervento
    public function update(int $id, array $data): array
    {
        $intervention = $this->repo->findById($id);

        if ($intervention === null) {
            throw new RuntimeException('Intervento non trovato.');
        }

        $fields = [];

        if (isset($data['title'])) {
            if (strlen(trim($data['title'])) < 3) {
                throw new InvalidArgumentException('Il titolo deve avere almeno 3 caratteri.');
            }
            $fields['title'] = trim($data['title']);
        }

        if (isset($data['description'])) {
            $fields['description'] = trim($data['description']);
        }

        if (isset($data['status'])) {
            if (!in_array($data['status'], self::ALLOWED_STATUSES, true)) {
                throw new InvalidArgumentException(
                    'Invalid status. Allowed values: ' . implode(', ', self::ALLOWED_STATUSES)
                );
            }
            $fields['status'] = $data['status'];
        }

        if (empty($fields)) {
            throw new InvalidArgumentException('Nessun campo valido per l\'aggiornamento.');
        }

        $this->repo->update($id, $fields);

        return $this->repo->findById($id);
    }

    // Elimina un intervento
    public function delete(int $id): void
    {
        $intervention = $this->repo->findById($id);

        if ($intervention === null) {
            throw new RuntimeException('Intervento non trovato.');
        }

        $this->repo->delete($id);
    }
}