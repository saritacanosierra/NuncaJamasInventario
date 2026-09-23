<?php
/**
 * Helper para subir y gestionar imágenes
 */

class ImageUploader {
    private $uploadDir;
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    private $maxSize = 5242880; // 5MB
    
    public function __construct($uploadDir = null) {
        $this->uploadDir = $uploadDir ?: UPLOAD_DIR;
        
        // Crear directorio si no existe
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Sube una imagen
     * @param array $file $_FILES['campo']
     * @param string $prefix Prefijo para el nombre del archivo
     * @return array ['success' => bool, 'filename' => string, 'error' => string]
     */
    public function upload($file, $prefix = 'producto') {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'error' => 'Parámetros inválidos'];
        }
        
        // Verificar errores
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Error al subir el archivo'];
        }
        
        // Verificar tamaño
        if ($file['size'] > $this->maxSize) {
            return ['success' => false, 'error' => 'El archivo es demasiado grande (máx. 5MB)'];
        }
        
        // Verificar tipo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['success' => false, 'error' => 'Tipo de archivo no permitido'];
        }
        
        $extensiones = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $extension = $extensiones[$mimeType] ?? '';
        if ($extension === '') {
            return ['success' => false, 'error' => 'Tipo de archivo no permitido'];
        }

        $filename = $prefix . '_' . uniqid() . '_' . time() . '.' . $extension;
        $filepath = $this->uploadDir . $filename;
        
        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'error' => 'Error al guardar el archivo'];
        }
        
        // Redimensionar si es necesario (solo si GD está disponible)
        if (function_exists('imagecreatefromjpeg')) {
            $this->resizeImage($filepath, 800, 800);
        }
        
        return ['success' => true, 'filename' => $filename];
    }
    
    /**
     * Elimina una imagen
     * @param string $filename Nombre del archivo
     * @return bool
     */
    public function delete($filename) {
        if ($filename && file_exists($this->uploadDir . $filename)) {
            return unlink($this->uploadDir . $filename);
        }
        return false;
    }
    
    /**
     * Redimensiona una imagen manteniendo proporción
     * Solo funciona si la extensión GD está habilitada
     */
    private function resizeImage($filepath, $maxWidth, $maxHeight) {
        // Verificar que GD esté disponible
        if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng')) {
            return; // Si GD no está disponible, no redimensionar
        }
        
        $imageInfo = getimagesize($filepath);
        if (!$imageInfo) return;
        
        list($width, $height, $type) = $imageInfo;
        
        // Si la imagen es más pequeña, no redimensionar
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return;
        }
        
        // Calcular nuevas dimensiones
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        
        // Crear imagen según tipo
        $src = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                if (function_exists('imagecreatefromjpeg')) {
                    $src = imagecreatefromjpeg($filepath);
                }
                break;
            case IMAGETYPE_PNG:
                if (function_exists('imagecreatefrompng')) {
                    $src = imagecreatefrompng($filepath);
                }
                break;
            case IMAGETYPE_GIF:
                if (function_exists('imagecreatefromgif')) {
                    $src = imagecreatefromgif($filepath);
                }
                break;
            default:
                return;
        }
        
        if (!$src) return; // Si no se pudo crear la imagen, salir
        
        // Crear nueva imagen
        $dst = imagecreatetruecolor($newWidth, $newHeight);
        if (!$dst) {
            imagedestroy($src);
            return;
        }
        
        // Preservar transparencia para PNG
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        
        // Redimensionar
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Guardar
        switch ($type) {
            case IMAGETYPE_JPEG:
                if (function_exists('imagejpeg')) {
                    imagejpeg($dst, $filepath, 85);
                }
                break;
            case IMAGETYPE_PNG:
                if (function_exists('imagepng')) {
                    imagepng($dst, $filepath, 8);
                }
                break;
            case IMAGETYPE_GIF:
                if (function_exists('imagegif')) {
                    imagegif($dst, $filepath);
                }
                break;
        }
        
        imagedestroy($src);
        imagedestroy($dst);
    }
}