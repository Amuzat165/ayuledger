<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReorderAuditColumns extends Migration
{
    public function up()
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $tables = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
        foreach ($tables as $tableInfo) {
            $tableName = array_values((array) $tableInfo)[0];
            $columns = DB::select('SHOW COLUMNS FROM ' . $this->quote($tableName));
            $columnNames = array_map(function ($column) {
                return $column->Field;
            }, $columns);

            if (!in_array('created_at', $columnNames, true) || !in_array('updated_at', $columnNames, true)) {
                continue;
            }

            $auditColumns = array_values(array_intersect(['created_at', 'updated_at', 'deleted_at'], $columnNames));
            $lastDataColumn = null;
            foreach ($columnNames as $columnName) {
                if (!in_array($columnName, $auditColumns, true)) {
                    $lastDataColumn = $columnName;
                }
            }

            foreach ($auditColumns as $auditColumn) {
                $definition = collect($columns)->firstWhere('Field', $auditColumn);
                $sql = 'ALTER TABLE ' . $this->quote($tableName)
                    . ' MODIFY ' . $this->quote($auditColumn)
                    . ' ' . $definition->Type
                    . ($definition->Null === 'YES' ? ' NULL' : ' NOT NULL');

                if ($definition->Default !== null) {
                    $default = is_string($definition->Default)
                        ? "'" . str_replace("'", "''", $definition->Default) . "'"
                        : $definition->Default;
                    $sql .= ' DEFAULT ' . $default;
                }

                if ($definition->Extra !== '') {
                    $sql .= ' ' . $definition->Extra;
                }

                $sql .= ' AFTER ' . $this->quote($lastDataColumn ?: $auditColumns[array_key_first($auditColumns)]);
                DB::statement($sql);
                $lastDataColumn = $auditColumn;
            }
        }
    }

    public function down()
    {
        // Column order has no effect on application behavior and is not reversed.
    }

    private function quote($identifier)
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
