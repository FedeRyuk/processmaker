<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiChatController extends Controller
{
    public function chat(Request $request, Project $project)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'page' => ['nullable', 'string', 'max:100'],
        ]);

        $context = $this->buildContext($project);
        $reply = $this->ask($data['message'], $context, $data['page'] ?? 'generale');

        return response()->json(['reply' => $reply]);
    }

    /**
     * Summarise the current process so the assistant can reason about it.
     */
    private function buildContext(Project $project): string
    {
        $project->load(['tasks.fields', 'transitions.fromTask', 'transitions.toTask', 'dbTables.columns']);

        $lines = ["Progetto: {$project->name}"];
        if ($project->description) {
            $lines[] = "Descrizione: {$project->description}";
        }

        $lines[] = "\nTask del processo:";
        foreach ($project->tasks as $task) {
            $flag = $task->is_initial ? ' [iniziale]' : '';
            $fields = $task->fields->map(fn ($f) => "{$f->label} ({$f->type})")->implode(', ');
            $lines[] = "- {$task->name}{$flag}: " . ($fields ?: 'nessun campo');
        }

        $lines[] = "\nTransizioni (frecce):";
        foreach ($project->transitions as $t) {
            $cond = $t->conditions ? json_encode($t->conditions, JSON_UNESCAPED_UNICODE) : 'nessuna condizione';
            $lines[] = "- {$t->fromTask?->name} -> {$t->toTask?->name} [{$cond}]";
        }

        $lines[] = "\nTabelle database:";
        foreach ($project->dbTables as $table) {
            $cols = $table->columns->map(fn ($c) => "{$c->name}:{$c->type}")->implode(', ');
            $lines[] = "- {$table->name} ({$cols})";
        }

        return implode("\n", $lines);
    }

    private function ask(string $message, string $context, string $page): string
    {
        $apiKey = config('services.openai.key');

        if (!$apiKey) {
            return $this->localSuggestion($message, $context, $page);
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Sei un assistente esperto di progettazione di processi aziendali (BPM). "
                                . "Aiuti l'utente a progettare task, flussi, campi e struttura del database. "
                                . "Rispondi in italiano, in modo conciso e pratico.\n\nContesto del processo:\n{$context}\n\nPagina corrente: {$page}",
                        ],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'temperature' => 0.3,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content') ?? 'Nessuna risposta.';
            }
        } catch (\Throwable $e) {
            // fall through to local suggestion
        }

        return $this->localSuggestion($message, $context, $page);
    }

    /**
     * Heuristic offline assistant used when no AI key is configured.
     */
    private function localSuggestion(string $message, string $context, string $page): string
    {
        $tips = [
            'flow' => "Suggerimenti per il flusso:\n• Definisci un task iniziale e collega i task con le frecce.\n• Su ogni freccia imposta le condizioni sui campi (es. importo > 1000) per indirizzare il caso.\n• Un task senza frecce in uscita è una foglia e chiude il caso.",
            'task' => "Suggerimenti per la progettazione del task:\n• Per ogni campo scegli il tipo adatto (testo, select, tabella…).\n• Imposta la mappatura di lettura (db/tabella/colonna o valore di default) e di scrittura.\n• Segna come obbligatori i campi necessari alla validazione finale.",
            'db' => "Suggerimenti per la struttura del database:\n• Aggiungi una chiave primaria auto-incrementale per ogni tabella.\n• Usa VARCHAR per testi brevi, TEXT per descrizioni, DECIMAL per importi.\n• Genera lo script MySQL con il pulsante apposito e rivedilo prima di eseguirlo.",
        ];

        $base = $tips[$page] ?? "Posso aiutarti su flusso, progettazione task e struttura del database. Configura OPENAI_API_KEY per risposte avanzate.";

        return $base . "\n\n--- Riepilogo del processo ---\n" . $context;
    }
}
