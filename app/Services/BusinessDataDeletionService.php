<?php

namespace App\Services;

use App\BusinessSetting;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BusinessDataDeletionService
{
    public function delete(BusinessSetting $business)
    {
        $businessId = (int) $business->getKey();

        $assets = DB::transaction(function () use ($businessId) {
            $business = DB::table('business_settings')
                ->where('id', $businessId)
                ->lockForUpdate()
                ->first();

            if (!$business) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            $tenantId = (int) $business->tenant_id;
            $users = DB::table('users')->where('business_id', $businessId)->get(['id', 'email']);
            $userIds = $users->pluck('id')->all();
            $emails = $users->pluck('email')->filter()->all();

            $proofPaths = DB::table('business_payment_verifications')
                ->where('business_id', $businessId)
                ->whereNotNull('proof_path')
                ->pluck('proof_path')
                ->filter()
                ->unique()
                ->values()
                ->all();
            $avatarPaths = DB::table('profiles')
                ->where('business_id', $businessId)
                ->whereNotNull('avatar')
                ->pluck('avatar')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $columnRows = DB::select(
                'SELECT c.TABLE_NAME AS table_name, c.COLUMN_NAME AS column_name
                FROM INFORMATION_SCHEMA.COLUMNS c
                INNER JOIN INFORMATION_SCHEMA.TABLES t
                    ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
                WHERE c.TABLE_SCHEMA = DATABASE()
                    AND t.TABLE_TYPE = ?
                    AND c.COLUMN_NAME IN (?, ?)
                ORDER BY c.TABLE_NAME, c.COLUMN_NAME',
                ['BASE TABLE', 'business_id', 'tenant_id']
            );

            $columns = [];
            $tables = [];
            foreach ($columnRows as $column) {
                $columns[$column->table_name][$column->column_name] = true;
                $tables[$column->table_name] = true;
            }

            foreach (['business_settings', 'tenants'] as $table) {
                $tables[$table] = true;
            }

            foreach ([
                'sessions' => 'user_id',
                'password_resets' => 'email',
                'password_reset_tokens' => 'email',
            ] as $table => $column) {
                if ($this->hasColumn($table, $column)) {
                    $tables[$table] = true;
                    $columns[$table][$column] = true;
                }
            }

            if ($this->hasColumn('notifications', 'notifiable_id')
                && $this->hasColumn('notifications', 'notifiable_type')) {
                $tables['notifications'] = true;
                $columns['notifications']['notifiable_id'] = true;
                $columns['notifications']['notifiable_type'] = true;
            }

            $order = $this->deletionOrder(array_keys($tables));
            foreach ($order as $table) {
                $query = DB::table($table);

                if ($table === 'business_settings') {
                    $query->where('id', $businessId)->delete();
                } elseif ($table === 'tenants') {
                    $query->where('id', $tenantId)->delete();
                } elseif ($table === 'sessions' && isset($columns[$table]['user_id'])) {
                    $query->whereIn('user_id', $userIds)->delete();
                } elseif (in_array($table, ['password_resets', 'password_reset_tokens'], true)
                    && isset($columns[$table]['email'])) {
                    $query->whereIn('email', $emails)->delete();
                } elseif ($table === 'notifications' && isset($columns[$table]['notifiable_id'])) {
                    $query->whereIn('notifiable_type', [User::class, 'App\\Models\\User'])
                        ->whereIn('notifiable_id', $userIds)
                        ->delete();
                } elseif (isset($columns[$table]['business_id'])) {
                    $query->where('business_id', $businessId)->delete();
                } elseif (isset($columns[$table]['tenant_id'])) {
                    $query->where('tenant_id', $tenantId)->delete();
                }
            }

            return [
                'business_id' => $businessId,
                'proof_paths' => $proofPaths,
                'avatar_paths' => $avatarPaths,
            ];
        });

        $failedFiles = $this->deleteUploadedFiles($assets);
        if ($failedFiles > 0) {
            Log::warning('Business data was deleted, but some uploaded files could not be removed.', [
                'business_id' => $assets['business_id'],
                'failed_file_count' => $failedFiles,
            ]);
        }

        return $failedFiles === 0;
    }

    private function deletionOrder(array $tables)
    {
        $tableSet = array_fill_keys($tables, true);
        $childrenByParent = [];
        $foreignKeys = DB::select(
            'SELECT TABLE_NAME AS child_table, REFERENCED_TABLE_NAME AS parent_table
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME IS NOT NULL'
        );

        foreach ($foreignKeys as $foreignKey) {
            if (isset($tableSet[$foreignKey->child_table], $tableSet[$foreignKey->parent_table])) {
                $childrenByParent[$foreignKey->parent_table][] = $foreignKey->child_table;
            }
        }

        $ordered = [];
        $visited = [];
        $visiting = [];
        $visit = function ($table) use (&$visit, &$ordered, &$visited, &$visiting, $childrenByParent) {
            if (isset($visited[$table])) {
                return;
            }
            if (isset($visiting[$table])) {
                throw new RuntimeException('Business data tables contain a cyclic foreign-key dependency.');
            }

            $visiting[$table] = true;
            foreach (array_unique($childrenByParent[$table] ?? []) as $childTable) {
                $visit($childTable);
            }
            unset($visiting[$table]);
            $visited[$table] = true;
            $ordered[] = $table;
        };

        foreach ($tables as $table) {
            $visit($table);
        }

        return $ordered;
    }

    private function hasColumn($table, $column)
    {
        return DB::table('INFORMATION_SCHEMA.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->exists();
    }

    private function deleteUploadedFiles(array $assets)
    {
        $failed = 0;

        foreach ($assets['proof_paths'] as $path) {
            if (DB::table('business_payment_verifications')->where('proof_path', $path)->exists()) {
                continue;
            }

            $disk = Storage::disk('public');
            if ($disk->exists($path) && !$disk->delete($path)) {
                $failed++;
            }
        }

        foreach ($assets['avatar_paths'] as $path) {
            if (DB::table('profiles')->where('avatar', $path)->exists()) {
                continue;
            }

            if (!$this->deleteAvatarFile($path)) {
                $failed++;
            }
        }

        return $failed;
    }

    private function deleteAvatarFile($path)
    {
        if (!preg_match('#\Aupload/avatar/[^/]+\z#', $path) || $path === 'upload/avatar/avatar.png') {
            return true;
        }

        $roots = [public_path(), base_path()];
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $roots[] = $_SERVER['DOCUMENT_ROOT'];
        }

        foreach (array_unique($roots) as $root) {
            $root = realpath($root);
            $file = realpath(rtrim($root ?: '', DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$path);
            if (!$root || !$file || strpos($file, $root.DIRECTORY_SEPARATOR) !== 0 || !is_file($file)) {
                continue;
            }

            return unlink($file);
        }

        return true;
    }
}
