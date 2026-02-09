<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentAttachment;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
    }

    public function showAttachment(Request $request, $parentID) {
        $attachmentData = DocumentAttachment::where('parent_id', $parentID)->get();
        return view('modules.attachment.index', [
            'parentID' => $parentID,
            'attachments' => $attachmentData,
        ]);
    }

    public function store(Request $request) {
        try {
            $parentID = $request->parent_id;
            $type = $request->type;
            $attachment = $request->file('attachment');

            $directory = $this->uploadFile($parentID, $attachment);

            if (!empty($directory)) {
                $attachmentData = DocumentAttachment::where([
                    ['parent_id', $parentID],
                    ['type', $type],
                    ['directory', $directory]
                ])->first();

                $instanceAttachment = $attachmentData ?? new DocumentAttachment;
                $instanceAttachment->parent_id = $parentID;
                $instanceAttachment->type = $type;
                $instanceAttachment->directory = $directory;
                $instanceAttachment->save();

                return response()->json([
                    'filename' => basename($directory),
                    'directory' => asset($directory) // Use asset() helper
                ]);
            } else {
                return response()->json([
                    'filename' => 'NULL',
                    'directory' => 'NULL'
                ], 400);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Error has occurred! Please try again.',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    private function uploadFile($parentID, $attachment) {
        $directory = "";

        if (!empty($attachment)) {
            $newFileName = $attachment->getClientOriginalName();

            // Store the file
            $path = $attachment->storeAs(
                'attachments/' . $parentID,
                $newFileName,
                'public' // This stores in storage/app/public
            );

            // Return the public path
            $directory = 'storage/' . $path;
        }

        return $directory;
    }

    public function destroy(Request $request, $id) {
        try {
            $instanceAttachment = DocumentAttachment::findOrFail($id);

            // Get the storage path (remove 'storage/' prefix)
            $storagePath = str_replace('storage/', '', $instanceAttachment->directory);

            // Delete from storage
            Storage::disk('public')->delete($storagePath);

            // Delete database record
            $instanceAttachment->delete();

            return response()->json(['message' => 'Successfully deleted.']);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Failed to delete attachment.'], 500);
        }
    }
}
