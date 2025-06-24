<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

// ここからクラス定義が始まり、ファイル内で1回だけ記述される
class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        $companies = Company::orderBy('name')->get();
        return view('companies.index', compact('companies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('companies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:companies,name',
            'emoji_identifier' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return redirect()->route('companies.create')
                        ->withErrors($validator)
                        ->withInput();
        }

        Company::create([
            'name' => $request->name,
            'emoji_identifier' => $request->emoji_identifier,
        ]);

        return redirect()->route('companies.index')
                         ->with('success', '会社情報が正常に登録されました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        // 後で実装
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company)
    {
        // 後で実装
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company)
    {
        // 後で実装
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        // 後で実装
    }
} // ここでクラス定義が終了。この後に同じクラス定義が続かないようにする。