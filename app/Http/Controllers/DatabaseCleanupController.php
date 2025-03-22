<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseCleanupController extends Controller
{   
    public function exportExcel(Request $request)
    {
        $tableName = $request->query('table_name');

        if (!$tableName) {
            return redirect()->route('settings.database')->with('error', 'Veuillez sélectionner une table.');
        }

        try {
            $data = DB::table($tableName)->get();

            if ($data->isEmpty()) {
                return redirect()->route('settings.database')->with('error', 'La table sélectionnée est vide.');
            }

            $fileName = $tableName . '_' . date('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                private $data;
                public function __construct($data) { $this->data = $data; }
                public function collection() { return $this->data; }
                public function headings(): array { return array_keys((array) $this->data->first()); }
            }, $fileName, ExcelFormat::XLSX);

        } catch (\Exception $e) {
            return redirect()->route('settings.database')->with('error', 'Erreur lors de l\'export : ' . $e->getMessage());
        }
    }
    public function exportCsv(Request $request)
    {
        $tableName = $request->query('table_name');

        if (!$tableName) {
            return redirect()->route('settings.database')->with('error', 'Veuillez sélectionner une table.');
        }

        try {
            // Récupérer les données de la table
            $data = DB::table($tableName)->get();

            if ($data->isEmpty()) {
                return redirect()->route('settings.database')->with('error', 'La table sélectionnée est vide.');
            }

            // Générer le nom du fichier
            $fileName = $tableName . '_' . date('Y-m-d_H-i-s') . '.csv';

            // Création du flux de sortie CSV
            $callback = function () use ($data) {
                $output = fopen('php://output', 'w');
                fputcsv($output, array_keys((array) $data->first()));

                foreach ($data as $row) {
                    fputcsv($output, (array) $row);
                }

                fclose($output);
            };

            // Retourner la réponse de téléchargement
            return response()->streamDownload($callback, $fileName, [
                'Content-Type' => 'text/csv',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ]);

        } catch (\Exception $e) {
            return redirect()->route('settings.database')->with('error', 'Erreur lors de l\'export : ' . $e->getMessage());
        }
    }


    public function importCsv(Request $request)
{
    $request->validate([
        'table_name' => 'required|string',
        'csv_file' => 'required|file|mimes:csv,txt'
    ]);

    $tableName = $request->input('table_name');
    $file = $request->file('csv_file');

    if (!Schema::hasTable($tableName)) {
        return redirect()->back()->with('error', 'La table sélectionnée n\'existe pas.');
    }

    $handle = fopen($file->getPathname(), "r");

    if ($handle !== false) {
        $columns = fgetcsv($handle, 0, ','); // Préciser la délimitation ("," ou ";")

        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            if (count($columns) !== count($data)) {
                continue; // Ignore les lignes incorrectes
            }

            $row = array_combine($columns, $data);

            if (!$row) {
                continue; // Ignore si array_combine échoue
            }

            // Convertir les valeurs vides en NULL
            foreach ($row as $key => $value) {
                $row[$key] = empty($value) ? null : $value;
            }

            try {
                DB::table($tableName)->insert($row);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', "Erreur lors de l'importation : " . $e->getMessage());
            }
        }

        fclose($handle);
    }

    return redirect()->back()->with('success', "Les données ont été importées dans la table $tableName avec succès !");
}

    public function getTables()
    {
        $tables = DB::select('SHOW TABLES');
        $tableNames = [];

        foreach ($tables as $table) {
            $tableNames[] = current((array) $table);
        }

        return response()->json($tableNames);
    }


    public function showDatabaseSettings()
    {
        return view('settings.database');
    }

    public function deleteAllData()
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        
            $tables = DB::select('SHOW TABLES');
            
            foreach ($tables as $table) {
                $tableName = current((array) $table);
                if ($tableName !== 'users') {
                    DB::table($tableName)->truncate();
                }
            }
        
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        
            Artisan::call('db:seed');
        
            Auth::logout();
        
            return redirect()->route('login')->with('success', 'La base de données a été réinitialisée. Veuillez vous reconnecter.');
        } catch (\Exception $e) {
            Log::error('Erreur lors du nettoyage de la base de données: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Une erreur est survenue lors du nettoyage de la base de données.');
        }
    }
}
