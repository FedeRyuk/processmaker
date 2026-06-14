<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\DbStructureController;
use App\Http\Controllers\FlowController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskDesignerController;
use Illuminate\Support\Facades\Route;

// Home: gestione dei progetti di processo
Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
Route::resource('projects', ProjectController::class)->except(['index']);

Route::prefix('projects/{project}')->group(function () {
    // Flusso
    Route::get('flow', [FlowController::class, 'index'])->name('flow.index');
    Route::post('flow/tasks', [FlowController::class, 'storeTask'])->name('flow.tasks.store');
    Route::post('flow/positions', [FlowController::class, 'updatePositions'])->name('flow.positions');
    Route::delete('flow/tasks/{task}', [FlowController::class, 'destroyTask'])->name('flow.tasks.destroy');
    Route::post('flow/transitions', [FlowController::class, 'storeTransition'])->name('flow.transitions.store');
    Route::put('flow/transitions/{transition}', [FlowController::class, 'updateTransition'])->name('flow.transitions.update');
    Route::delete('flow/transitions/{transition}', [FlowController::class, 'destroyTransition'])->name('flow.transitions.destroy');

    // Progettazione task
    Route::get('tasks/designer', [TaskDesignerController::class, 'all'])->name('tasks.designer.all');
    Route::get('tasks/{task}/designer', [TaskDesignerController::class, 'index'])->name('tasks.designer');
    Route::post('tasks/{task}/fields', [TaskDesignerController::class, 'storeField'])->name('tasks.fields.store');
    Route::put('tasks/{task}/fields/{field}', [TaskDesignerController::class, 'updateField'])->name('tasks.fields.update');
    Route::delete('tasks/{task}/fields/{field}', [TaskDesignerController::class, 'destroyField'])->name('tasks.fields.destroy');

    // Struttura database
    Route::get('db-structure', [DbStructureController::class, 'index'])->name('db.index');
    Route::post('db-structure/tables', [DbStructureController::class, 'storeTable'])->name('db.tables.store');
    Route::put('db-structure/tables/{table}', [DbStructureController::class, 'updateTable'])->name('db.tables.update');
    Route::delete('db-structure/tables/{table}', [DbStructureController::class, 'destroyTable'])->name('db.tables.destroy');
    Route::post('db-structure/tables/{table}/columns', [DbStructureController::class, 'storeColumn'])->name('db.columns.store');
    Route::put('db-structure/tables/{table}/columns/{column}', [DbStructureController::class, 'updateColumn'])->name('db.columns.update');
    Route::delete('db-structure/tables/{table}/columns/{column}', [DbStructureController::class, 'destroyColumn'])->name('db.columns.destroy');
    Route::get('db-structure/script/{table?}', [DbStructureController::class, 'script'])->name('db.script');

    // Esecuzione casi
    Route::get('cases', [CaseController::class, 'index'])->name('cases.index');
    Route::post('cases', [CaseController::class, 'store'])->name('cases.store');
    Route::get('cases/{case}/tasks/{caseTask}', [CaseController::class, 'showTask'])->name('cases.task');
    Route::post('cases/{case}/tasks/{caseTask}/draft', [CaseController::class, 'saveDraft'])->name('cases.task.draft');
    Route::post('cases/{case}/tasks/{caseTask}/complete', [CaseController::class, 'complete'])->name('cases.task.complete');

    // Assistente IA
    Route::post('ai/chat', [AiChatController::class, 'chat'])->name('ai.chat');
});
