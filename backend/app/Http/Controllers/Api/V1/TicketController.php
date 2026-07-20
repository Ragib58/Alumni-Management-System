<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Jobs\SendTicketEmailJob;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
        private readonly TicketService $ticketService,
    ) {
    }

    /**
     * GET /api/v1/my-tickets
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 10);

        return $this->success(
            TicketResource::collection($this->tickets->paginateForUser($request->user()->id, $perPage)),
            'Your tickets retrieved successfully.'
        );
    }

    /**
     * GET /api/v1/tickets/{ticket}
     */
    public function show(int $ticket): JsonResponse
    {
        $model = $this->tickets->findOrFail($ticket)->load('registration.event', 'registration.user');
        $this->authorize('view', $model);

        return $this->success(new TicketResource($model), 'Ticket retrieved.');
    }

    /**
     * GET /api/v1/tickets/{ticket}/download — streams the PDF (regenerating if needed).
     */
    public function download(int $ticket): BinaryFileResponse
    {
        $model = $this->tickets->findOrFail($ticket);
        $this->authorize('download', $model);

        $path = $this->ticketService->ensurePdfPath($model);

        return response()->download($path, $model->ticket_no.'.pdf');
    }

    /**
     * POST /api/v1/tickets/{ticket}/email — (re)send the ticket by email.
     */
    public function email(int $ticket): JsonResponse
    {
        $model = $this->tickets->findOrFail($ticket);
        $this->authorize('view', $model);

        SendTicketEmailJob::dispatch($model->id);

        return $this->success(null, 'Ticket will be emailed shortly.');
    }
}
