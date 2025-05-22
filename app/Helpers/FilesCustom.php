<?php

namespace App\Helpers;

trait FilesCustom
{
    /**
     * Retorna o tamanho do arquivo formatado em KB, MB ou GB
     *
     * @param string $path
     * @return string
     */
    public function getFormattedFileSize(string $path): string
    {
        if (!file_exists($path)) {
            return '0 KB';
        }

        $size = filesize($path);
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $size > 0 ? floor(log($size, 1024)) : 0;

        return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }

}
