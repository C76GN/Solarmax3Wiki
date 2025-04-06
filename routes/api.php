<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::middleware('auth:sanctum')->post('/upload-image', function (Request $request) {
    if ($request->hasFile('image') && $request->file('image')->isValid()) {
        $path = $request->file('image')->store('uploads/images', 'public');
        return response()->json(['url' => asset('storage/' . $path)]);
    }
    return response()->json(['error' => '图片上传失败'], 400);
});