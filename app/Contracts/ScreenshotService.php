<?php

namespace App\Contracts;

use App\Jobs\GenerateScreenshotThumbnail;
use App\Models\TimeInterval;
use Image;
use Intervention\Image\Constraint;
use Storage;
use Illuminate\Support\Facades\Log;

abstract class ScreenshotService
{
    protected const FILE_FORMAT = 'jpg';
    public const PARENT_FOLDER = 'screenshots/';
    public const THUMBS_FOLDER = 'thumbs/';
    private const THUMB_WIDTH = 280;
    private const QUALITY = 50;

    /** Get screenshot path by interval */
    abstract public function getScreenshotPath(TimeInterval|int $interval): string;
    /** Get screenshot thumbnail path by interval */
    abstract public function getThumbPath(TimeInterval|int $interval): string;

    public function saveScreenshot($file, $timeInterval): void
    {
        if (!Storage::exists(self::PARENT_FOLDER)) {
            Storage::makeDirectory(self::PARENT_FOLDER);
        }

        $path = is_string($file) ? $file : $file->path();

        $image = Image::make($path);

        Storage::put($this->getScreenshotPath($timeInterval), (string)$image->encode(self::FILE_FORMAT, self::QUALITY));

        GenerateScreenshotThumbnail::dispatch($timeInterval);
    }

    public function createThumbnail(TimeInterval|int $timeInterval): void
    {
        Log::info('Iniciando createThumbnail para el intervalo: ' . (is_object($timeInterval) ? $timeInterval->id : $timeInterval));

        $thumbsFolderFullPath = self::PARENT_FOLDER . self::THUMBS_FOLDER;
        if (!Storage::exists($thumbsFolderFullPath)) {
            Storage::makeDirectory($thumbsFolderFullPath);
            Log::info('Carpeta THUMBS_FOLDER creada: ' . $thumbsFolderFullPath);
        }

        $originalScreenshotPath = $this->getScreenshotPath($timeInterval);
        $fullOriginalPath = Storage::path($originalScreenshotPath);

        Log::info('Ruta completa de la captura original para thumbnail: ' . $fullOriginalPath);

        if (!Storage::exists($originalScreenshotPath)) {
            Log::error('¡La captura de pantalla original NO existe para crear la miniatura en: ' . $originalScreenshotPath);
            return; // O lanza una excepción, ya que no se puede crear la miniatura sin el original
        }

        try {
            $image = Image::make($fullOriginalPath);
            Log::info('Imagen original cargada para miniatura desde: ' . $fullOriginalPath);
        } catch (\Exception $e) {
            Log::error('Error al cargar la imagen original para miniatura con Intervention/Image: ' . $e->getMessage() . ' desde path: ' . $fullOriginalPath);
            return;
        }

        try {
            $thumb = $image->resize(self::THUMB_WIDTH, null, fn(Constraint $constraint) => $constraint->aspectRatio());
            Log::info('Imagen redimensionada para miniatura.');
        } catch (\Exception $e) {
            Log::error('Error al redimensionar la imagen para miniatura: ' . $e->getMessage());
            return;
        }

        $thumbSavePath = $this->getThumbPath($timeInterval);

        Log::info('Intentando guardar miniatura en: ' . $thumbSavePath);

        try {
            Storage::put($thumbSavePath, (string)$thumb->encode(self::FILE_FORMAT, self::QUALITY));
            Log::info('Miniatura guardada exitosamente en: ' . $thumbSavePath);
        } catch (\Exception $e) {
            Log::error('Error al guardar la miniatura: ' . $e->getMessage() . ' en path: ' . $thumbSavePath);
            // También puedes loguear más detalles de la excepción, como $e->getTraceAsString()
        }
    }

    public function destroyScreenshot(TimeInterval|int $interval): void
    {
        Storage::delete($this->getScreenshotPath($interval));
        Storage::delete($this->getThumbPath($interval));
    }

    public static function getFullPath(): string
    {
        $fileSystemPath = config('filesystems.default');
        return storage_path(config("filesystems.disks.$fileSystemPath.root"));
    }
}
