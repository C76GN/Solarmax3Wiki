<?php
// FileName: /var/www/Solarmax3Wiki/app/Http/Controllers/GameWikiController.php


namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class GameWikiController extends Controller
{
    public function index()
    {
        return Inertia::render('Solarmax3Wiki/GameWiki'); // 假设你用的是 Inertia.js
    }

}
