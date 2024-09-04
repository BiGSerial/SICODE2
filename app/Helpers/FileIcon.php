<?php

namespace App\Helpers;

use Hamcrest\Core\IsTypeOf;

class FileIcon
{
    public static function getIcon(string $ext)
    {


        if (!$ext) {
            return '';
        }

        switch ($ext) {
            case "pdf":
                return (object)['icon' => 'bx bxs-file-pdf text-danger'];
                break;
            case "xls":
                return (object)['icon' => 'ri-file-excel-line text-success'];
                break;
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
            default:
                return (object)['icon' => 'ri-file-fill text-danger'];
                break;
        }
    }
}
