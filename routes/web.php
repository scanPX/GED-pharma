<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| GED Pharma - Système de Gestion Documentaire
| Routes pour l'application SPA Vue.js
|
*/

// Route d'accueil Laravel (optionnel)
Route::get('/welcome', function () {
    return view('welcome');
});

// Route principale de l'application GED
// Cette route capture toutes les URLs et laisse Vue Router gérer le routage côté client
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*$')->name('spa');
