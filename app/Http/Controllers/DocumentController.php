<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller {
    public function store(Request $request, User $employee) {
        $user = $request->user();
        if (!$user->isManager()) abort(403);
        $data = $request->validate([
            "title" => "required|string|max:200",
            "category" => "required|string|max:50",
            "expiry_date" => "nullable|date",
            "document" => "required|file|max:10240",
        ]);
        $file = $request->file("document");
        $path = $file->store("documents/{$employee->id}", "local");
        Document::create([
            "user_id" => $employee->id,
            "org_id" => $employee->org_id,
            "title" => $data["title"],
            "category" => $data["category"],
            "expiry_date" => $data["expiry_date"] ?? null,
            "file_path" => $path,
            "file_name" => $file->getClientOriginalName(),
            "mime_type" => $file->getMimeType(),
            "file_size" => $file->getSize(),
            "uploaded_by" => $user->id,
        ]);
        return redirect()->route("employees.show", $employee)->with("success","Document uploaded.");
    }

    public function download(Document $document) {
        $user = auth()->user();
        if ($document->user_id !== $user->id && !$user->isManager()) abort(403);
        if (!Storage::disk("local")->exists($document->file_path)) abort(404);
        return Storage::disk("local")->download($document->file_path, $document->file_name);
    }
}
