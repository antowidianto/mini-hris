<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\GenerateDocumentRequest;
use App\Http\Requests\Documents\ListDocumentsRequest;
use App\Http\Requests\Documents\UploadDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\DocumentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documentService) {}

    public function index(ListDocumentsRequest $request): JsonResponse
    {
        $documents = $this->documentService->paginate($request->validated(), $request->user());
        $payload = DocumentResource::collection($documents)->response()->getData(true);

        return ApiResponse::success('Documents retrieved', [
            'documents' => $payload['data'],
            'links' => $payload['links'],
            'meta' => $payload['meta'],
        ]);
    }

    public function mine(ListDocumentsRequest $request): JsonResponse
    {
        $documents = $this->documentService->employeeDocuments($request->user(), $request->validated());
        $payload = DocumentResource::collection($documents)->response()->getData(true);

        return ApiResponse::success('My documents retrieved', [
            'documents' => $payload['data'],
            'links' => $payload['links'],
            'meta' => $payload['meta'],
        ]);
    }

    public function generate(GenerateDocumentRequest $request): JsonResponse
    {
        $document = $this->documentService->generate($request->validated(), $request->user());

        return ApiResponse::success('Document generated successfully', [
            'document' => new DocumentResource($document),
        ], 201);
    }

    public function upload(UploadDocumentRequest $request): JsonResponse
    {
        $document = $this->documentService->upload(
            $request->validated(),
            $request->file('file'),
            $request->user()
        );

        return ApiResponse::success('Document uploaded successfully', [
            'document' => new DocumentResource($document),
        ], 201);
    }

    public function preview(Document $document): Response
    {
        return $this->documentService->preview($document, request()->user());
    }

    public function download(Document $document): Response
    {
        return $this->documentService->download($document, request()->user());
    }

    public function destroy(Document $document): JsonResponse
    {
        $this->documentService->delete($document, request()->user());

        return ApiResponse::success('Document deleted successfully');
    }
}
