<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $siswa = Siswa::all();
        return response()->json([
            'message' => 'List of all siswa',
            'data' => $siswa
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string',
            'kelas' => 'required|string',
            'NIS' => 'required|string|unique:siswas,NIS',
        ]);

        $siswa = Siswa::create($request->all());

        return response()->json([
            'message' => 'Siswa created successfully',
            'data' => $siswa
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $siswa = Siswa::find($id);

        if (!$siswa) {
            return response()->json([
                'message' => 'Siswa not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Siswa details',
            'data' => $siswa
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $siswa = Siswa::find($id);

        if (!$siswa) {
            return response()->json([
                'message' => 'Siswa not found'
            ], 404);
        }

        $request->validate([
            'nama' => 'sometimes|required|string',
            'kelas' => 'sometimes|required|string',
            'NIS' => 'sometimes|required|string|unique:siswas,NIS,' . $id,
        ]);

        $siswa->update($request->all());

        return response()->json([
            'message' => 'Siswa updated successfully',
            'data' => $siswa
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $siswa = Siswa::find($id);

        if (!$siswa) {
            return response()->json([
                'message' => 'Siswa not found'
            ], 404);
        }

        $siswa->delete();

        return response()->json([
            'message' => 'Siswa deleted successfully'
        ], 200);
    }
}
