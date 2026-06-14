<?php

namespace App\Services;

use App\Models\DbTable;

class MysqlScriptGenerator
{
    /**
     * Build a CREATE TABLE statement for the given table definition.
     */
    public function createTable(DbTable $table): string
    {
        $lines = [];
        $primaryKeys = [];

        foreach ($table->columns as $col) {
            $lines[] = '  ' . $this->columnDefinition($col);
            if ($col->is_primary) {
                $primaryKeys[] = "`{$col->name}`";
            }
        }

        if ($primaryKeys) {
            $lines[] = '  PRIMARY KEY (' . implode(', ', $primaryKeys) . ')';
        }

        $sql = "CREATE TABLE IF NOT EXISTS `{$table->name}` (\n";
        $sql .= implode(",\n", $lines);
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        if ($table->comment) {
            $sql .= " COMMENT='" . addslashes($table->comment) . "'";
        }
        $sql .= ';';

        return $sql;
    }

    /**
     * Build a single column definition fragment.
     */
    public function columnDefinition($col): string
    {
        $type = strtoupper($col->type);
        $def = "`{$col->name}` {$type}";

        if ($col->length && in_array($type, ['VARCHAR', 'CHAR', 'INT', 'BIGINT', 'DECIMAL'])) {
            $def .= "({$col->length})";
        }

        $def .= $col->nullable ? ' NULL' : ' NOT NULL';

        if ($col->auto_increment) {
            $def .= ' AUTO_INCREMENT';
        } elseif ($col->default !== null && $col->default !== '') {
            $default = is_numeric($col->default) ? $col->default : "'" . addslashes($col->default) . "'";
            $def .= " DEFAULT {$default}";
        }

        return $def;
    }

    /**
     * Generate the full script for every table of a project.
     */
    public function script($tables): string
    {
        $parts = [];
        foreach ($tables as $table) {
            $parts[] = $this->createTable($table);
        }
        return implode("\n\n", $parts);
    }
}
