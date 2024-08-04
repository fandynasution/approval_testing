<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnvController extends Controller
{
    public function index()
    {
        try {
            DB::connection('BTID')->getPdo();
            echo "Connected successfully to database.";
        } catch (\Exception $e) {
            echo "Could not connect to the database. Please check your configuration. Error: " . $e->getMessage();
        }
    }
}
