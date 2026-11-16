<?php

namespace App\Http\Controllers;

use App\Exports\ExportRegistry;
use App\Models\AuditLog;
use App\Models\Category;
use App\Support\CsvExporter;
use Illuminate\Http\Request;
use ZipArchive;

class ExportController extends Controller
{
    public function index()
    {
        return view('exports.index', [
            'datasets' => ExportRegistry::forRole(auth()->user()->role),
            'categories' => Category::orderBy('category_name')->get(),
        ]);
    }

    public function download(Request $request, string $dataset)
    {
        $definition = $this->allowedDataset($dataset);
        $request->validate($this->rules());
        AuditLog::record('Exported', $dataset);

        return CsvExporter::download(
            $dataset . '-' . now()->format('Ymd-His') . '.csv',
            $definition['headers'],
            $definition['rows']($request)
        );
    }

    public function all()
    {
        if (!class_exists(ZipArchive::class)) {
            return back()->with('warning', 'Enable the zip extension in php.ini to download all exports.');
        }

        $role = auth()->user()->role;
        $datasets = ExportRegistry::forRole($role);
        $filename = 'ims-he-exports-' . now()->format('Ymd-His') . '.zip';
        $path = storage_path('app' . DIRECTORY_SEPARATOR . $filename);
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('warning', 'The export ZIP could not be created.');
        }

        $temporaryFiles = [];
        foreach ($datasets as $definition) {
            $temporary = tempnam(storage_path('app'), 'export-');
            $temporaryFiles[] = $temporary;
            $out = fopen($temporary, 'wb');
            CsvExporter::write($out, $definition['headers'], $definition['rows'](request()));
            fclose($out);
            $zip->addFile($temporary, $definition['key'] . '.csv');
        }

        $zip->close();
        foreach ($temporaryFiles as $temporary) {
            @unlink($temporary);
        }

        AuditLog::record('Exported', 'all');

        return response()->download($path, $filename, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    public function backup()
    {
        if (config('database.default') !== 'sqlite') {
            return back()->with('warning', 'Use mysqldump to create a database backup for this database driver.');
        }

        $path = database_path('database.sqlite');
        if (!is_file($path)) {
            return back()->with('warning', 'The SQLite database file could not be found.');
        }

        $filename = 'ims-he-backup-' . now()->format('Ymd-His') . '.sqlite';
        AuditLog::record('Exported', 'database');

        return response()->download($path, $filename, ['Content-Type' => 'application/vnd.sqlite3']);
    }

    private function allowedDataset(string $key): array
    {
        $definition = ExportRegistry::find($key);
        if (!$definition || !in_array(auth()->user()->role, $definition['roles'], true)) {
            abort(404);
        }

        return $definition;
    }

    private function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'category_id' => ['nullable', 'integer', 'exists:categories,category_id'],
            'status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
