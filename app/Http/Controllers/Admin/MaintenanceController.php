<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MaintenanceController extends Controller
{
    public function index()
    {
        $cacheSize = $this->getCacheSize();
        $logsSize = $this->getLogsSize();
        $storageUsed = $this->getStorageUsed();
        $dbSize = $this->getDatabaseSize();

        return view('admin.maintenance.index', compact('cacheSize', 'logsSize', 'storageUsed', 'dbSize'));
    }

    public function clearCache()
    {
        try {
            Cache::flush();
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');

            return redirect()->route('admin.maintenance.index')
                ->with('success', 'تم مسح ذاكرة التخزين المؤقت بنجاح');
        } catch (\Exception $e) {
            return redirect()->route('admin.maintenance.index')
                ->with('error', 'حدث خطأ أثناء مسح الذاكرة المؤقتة');
        }
    }

    public function clearLogs()
    {
        try {
            $logPath = storage_path('logs');
            $files = glob($logPath . '/*.log');

            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== '.gitignore') {
                    unlink($file);
                }
            }

            return redirect()->route('admin.maintenance.index')
                ->with('success', 'تم حذف ملفات السجلات بنجاح');
        } catch (\Exception $e) {
            return redirect()->route('admin.maintenance.index')
                ->with('error', 'حدث خطأ أثناء حذف السجلات');
        }
    }

    public function optimizeSystem()
    {
        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            return redirect()->route('admin.maintenance.index')
                ->with('success', 'تم تحسين النظام بنجاح');
        } catch (\Exception $e) {
            return redirect()->route('admin.maintenance.index')
                ->with('error', 'حدث خطأ أثناء تحسين النظام');
        }
    }

    public function systemInfo()
    {
        $info = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'غير معروف',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'debug_mode' => config('app.debug') ? 'مفعل' : 'معطل',
            'environment' => config('app.env'),
        ];

        return view('admin.maintenance.system-info', compact('info'));
    }

    private function getCacheSize(): string
    {
        $size = 0;
        $path = storage_path('framework/cache');

        if (is_dir($path)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }

        return $this->formatBytes($size);
    }

    private function getLogsSize(): string
    {
        $size = 0;
        $path = storage_path('logs');

        if (is_dir($path)) {
            foreach (glob($path . '/*.log') as $file) {
                $size += filesize($file);
            }
        }

        return $this->formatBytes($size);
    }

    private function getStorageUsed(): string
    {
        $size = 0;
        $path = storage_path('app/public');

        if (is_dir($path)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }

        return $this->formatBytes($size);
    }

    private function getDatabaseSize(): string
    {
        try {
            $dbName = config('database.connections.mysql.database');
            $result = DB::select("SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = ?", [$dbName]);

            return $this->formatBytes($result[0]->size ?? 0);
        } catch (\Exception $e) {
            return 'غير متاح';
        }
    }

    private function formatBytes($bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
