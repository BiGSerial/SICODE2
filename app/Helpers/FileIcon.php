<?php

namespace App\Helpers;

class FileIcon
{
    public static function getIcon($ext)
    {
        if (!$ext) {
            return (object) ['icon' => 'ri-file-fill text-danger'];
        }

        $ext = strtolower(trim((string) $ext));

        switch ($ext) {
            case "pdf":
                return (object)['icon' => 'bx bxs-file-pdf text-danger'];
                break;
            case "xls":
            case "xlsx":
                return (object)['icon' => 'ri-file-excel-line text-success'];
                break;
            case "jpg":
                return (object)['icon' => 'bx bxs-file-jpg text-secondary'];
                break;
            case "png":
                return (object)['icon' => 'bx bxs-file-png text-secondary'];
                break;
            case "doc":
            case "docx":
                return (object)['icon' => 'ri-file-word-line text-primary'];
                break;
            case "dwg":
            case "dxf":
                return (object)['icon' => 'bx bxl-windows text-primary'];
                break;
            case "skp":
                return (object)['icon' => 'bx bxs-cube text-warning'];
                break;
            default:
                return (object) ['icon' => 'ri-file-fill text-danger'];
                break;
        }
    }
}
