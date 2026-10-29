<?php

use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\ContentEntryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PhotoController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/',[PublicController::class,'home'])->name('home');
Route::get('/profil',[PublicController::class,'section'])->defaults('section','profil')->name('profile');
Route::get('/akademik',[PublicController::class,'section'])->defaults('section','akademik')->name('academics');
Route::get('/berita',[PublicController::class,'section'])->defaults('section','berita')->name('news');
Route::get('/berita/{slug}',[PublicController::class,'article'])->name('article.show');
Route::get('/pengumuman',[PublicController::class,'section'])->defaults('section','pengumuman')->name('announcements');
Route::get('/ekstrakurikuler',[PublicController::class,'section'])->defaults('section','ekskul')->name('extracurriculars');
Route::get('/ppdb',[PublicController::class,'section'])->defaults('section','ppdb')->name('ppdb');
Route::get('/galeri',[PublicController::class,'section'])->defaults('section','galeri')->name('gallery');
Route::get('/kontak',[PublicController::class,'section'])->defaults('section','kontak')->name('contact');
Route::post('/kontak',[PublicController::class,'contact'])->middleware('throttle:5,1')->name('contact.store');

Route::middleware('guest')->group(function(){Route::get('/login',[AuthController::class,'showLogin'])->name('login');Route::post('/login',[AuthController::class,'login'])->middleware('throttle:5,1')->name('login.store');});
Route::post('/logout',[AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::prefix('admin')->name('admin.')->middleware(['auth','role:admin,teacher'])->group(function(){
    Route::get('/',DashboardController::class)->name('dashboard');
    Route::get('/profile',[UserController::class,'editProfile'])->name('profile.edit');
    Route::put('/profile',[UserController::class,'profile'])->name('profile.update');
    Route::get('/articles',[ArticleController::class,'index'])->name('articles.index');
    Route::get('/articles/create',[ArticleController::class,'create'])->name('articles.create');
    Route::post('/articles',[ArticleController::class,'store'])->name('articles.store');
    Route::get('/articles/reviews',[ArticleController::class,'reviews'])->middleware('role:admin')->name('articles.reviews');
    Route::get('/articles/{article}/preview',[ArticleController::class,'preview'])->middleware('role:admin')->name('articles.preview');
    Route::post('/articles/{article}/review',[ArticleController::class,'review'])->middleware('role:admin')->name('articles.review');
    Route::post('/articles/{article}/unpublish',[ArticleController::class,'unpublish'])->middleware('role:admin')->name('articles.unpublish');
    Route::post('/articles/media',[ArticleController::class,'uploadImage'])->name('articles.media');
    Route::post('/articles/import-docx',[ArticleController::class,'importDocx'])->name('articles.import');
    Route::get('/articles/{article}/edit',[ArticleController::class,'edit'])->name('articles.edit');
    Route::put('/articles/{article}',[ArticleController::class,'update'])->name('articles.update');
    Route::delete('/articles/{article}',[ArticleController::class,'destroy'])->name('articles.destroy');
    Route::middleware('role:admin')->group(function(){
        Route::get('/photos/{placement}/edit',[PhotoController::class,'edit'])->name('photos.edit');
        Route::put('/photos/{placement}',[PhotoController::class,'update'])->name('photos.update');
        Route::get('/categories',[CategoryController::class,'index'])->name('categories.index');
        Route::post('/categories',[CategoryController::class,'store'])->name('categories.store');
        Route::put('/categories/{category}',[CategoryController::class,'update'])->name('categories.update');
        Route::delete('/categories/{category}',[CategoryController::class,'destroy'])->name('categories.destroy');
        Route::get('/users',[UserController::class,'index'])->name('users.index');
        Route::post('/users',[UserController::class,'store'])->name('users.store');
        Route::put('/users/{user}',[UserController::class,'update'])->name('users.update');
        Route::get('/content/{type}',[ContentEntryController::class,'index'])->name('content.index');
        Route::get('/content/{type}/create',[ContentEntryController::class,'create'])->name('content.create');
        Route::post('/content/{type}',[ContentEntryController::class,'store'])->name('content.store');
        Route::get('/content/{type}/{entry}/edit',[ContentEntryController::class,'edit'])->name('content.edit');
        Route::put('/content/{type}/{entry}',[ContentEntryController::class,'update'])->name('content.update');
        Route::delete('/content/{type}/{entry}',[ContentEntryController::class,'destroy'])->name('content.destroy');
        Route::get('/settings',[SettingsController::class,'edit'])->name('settings.edit');
        Route::put('/settings',[SettingsController::class,'update'])->name('settings.update');
        Route::get('/messages',[ContactMessageController::class,'index'])->name('messages.index');
        Route::patch('/messages/{message}',[ContactMessageController::class,'markRead'])->name('messages.read');
        Route::delete('/messages/{message}',[ContactMessageController::class,'destroy'])->name('messages.destroy');
    });
});
