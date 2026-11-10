<?php

namespace App\Http\Controllers;

use App\Models\Books;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;

class BooksController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Books = Books::all();
        return response()->json([
            'message' => 'List of all Books',
            'data' => $Books
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'author' => 'required|string',
            'publisher' => 'required|string',
            'code' => 'required|integer|unique:books,code',
        ]);
        $Books = Books::create($request->all());

        return response()->json([
            'message' => 'Books created successfully',
            'data' => $Books
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $id = Books::find($id);

        if (!$id) {
            return response()->json([
                'message' => 'Books not found'
            ], 404);
        }
        return response()->json([
            'message' => 'Books details',
            'data' => $id
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
         $Books = Books::find($id);

        if (!$Books) {
            return response()->json([
                'message' => 'BUku not found'
            ], 404);
        }

        $request->validate([
            'nama' => 'sometimes|required|string',
            'kelas' => 'sometimes|required|string',
            'NIS' => 'sometimes|required|string|unique:siswas,NIS,' . $id,
        ]);

        $Books->update($request->all());

        return response()->json([
            'message' => 'Siswa updated successfully',
            'data' => $Books
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
          $Books = Books::find($id);

        if (!$Books) {
            return response()->json([
                'message' => 'Siswa not found'
            ], 404);
        }

        $Books->delete();

        return response()->json([
            'message' => 'Siswa deleted successfully'
        ], 200);
    }
    }

