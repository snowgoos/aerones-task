<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileStoreRequest;
use App\Jobs\DownloadFileJob;
use App\Models\File;
use App\Repositories\FileRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FileController extends Controller
{
    public function __construct(private readonly FileRepository $fileRepository)
    {
    }

    public function index(): View
    {
        return view('pages.file.index', [
            'files' => $this->fileRepository->findAll(),
        ]);
    }

    public function create(): View
    {
        return view('pages.file.create_edit');
    }

    public function store(FileStoreRequest $request): RedirectResponse
    {
        $file = new File();
        $file->name = $request->get('name');
        $file->url = $request->get('url');
        $file->status = File::STATUS_PENDING; // TODO: move to the listener/observer
        $file->save();

        return redirect()->route('files.index');
    }

    public function forceDownload(Request $request): RedirectResponse
    {
        $files = $this->fileRepository->getWhereIdIn($request->get('force_download_ids'));

        DownloadFileJob::dispatch($files->pluck('url')->toArray());

        return redirect()->route('files.index');
    }

    // TODO: move to the API controller
    public function getProgress(): JsonResponse
    {
        $files = $this->fileRepository->findAll();

        // TODO: Create resource response

        return response()->json($files);
    }
}
