<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehiclePhoto;
use App\Services\ServicioArchivos;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Entrega los archivos que suben los usuarios, solo con sesión y permiso.
 *
 * Cada tipo pide lo mismo que la sección de la ficha donde se muestra. Los id
 * son consecutivos y se adivinan: si alguien no ve esa sección, tampoco puede
 * pedir el archivo escribiendo el id a mano.
 */
class ArchivoController extends Controller
{
    /**
     * Lo único que se muestra en el navegador; cualquier otro tipo se descarga.
     * Las subidas ya filtran el formato, esto es por si algo se cuela.
     */
    private const EN_LINEA = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'pdf' => 'application/pdf',
    ];

    /** Foto del vehículo (la ve quien ve la ficha) o de un gasto (además, quien ve los gastos). */
    public function foto(Request $request, VehiclePhoto $foto)
    {
        $this->autorizar($request, $foto->vehiculo, $foto->expense_id ? 'registrar gastos' : null);

        // ?tam=mini: la misma foto, más chica, con los mismos permisos. Si la
        // miniatura falta en el disco se entrega la foto: mejor pesada que rota.
        $ruta = $request->query('tam') === 'mini'
            && filled($foto->ruta_miniatura)
            && app(ServicioArchivos::class)->discoDe($foto->ruta_miniatura)
                ? $foto->ruta_miniatura
                : $foto->ruta;

        return $this->entregar($request, $ruta, $foto->nombre_original);
    }

    /** Documento del vehículo: la sección "Fotos, VIN y documentos". */
    public function documento(Request $request, VehicleDocument $documento)
    {
        $this->autorizar($request, $documento->vehiculo, 'completar datos del vehiculo');

        return $this->entregar($request, $documento->ruta, $documento->nombre_original);
    }

    /** Contrato de venta: la sección de venta lo muestra a todo el que ve la ficha. */
    public function contrato(Request $request, Sale $venta)
    {
        $this->autorizar($request, $venta->vehiculo);

        return $this->entregar($request, $venta->contrato_ruta, $venta->contrato_nombre);
    }

    private function autorizar(Request $request, ?Vehicle $vehiculo, ?string $permiso = null): void
    {
        // Vehículo borrado: su ficha da 404, sus archivos también.
        abort_if($vehiculo === null, 404);

        $usuario = $request->user();

        abort_unless(
            $usuario->can('view', $vehiculo) && ($permiso === null || $usuario->can($permiso)),
            403,
        );
    }

    private function entregar(Request $request, ?string $ruta, ?string $nombre): BinaryFileResponse
    {
        $disco = filled($ruta) ? app(ServicioArchivos::class)->discoDe($ruta) : null;
        abort_if($disco === null, 404);

        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $tipo = self::EN_LINEA[$extension] ?? null;

        $respuesta = new BinaryFileResponse($disco->path($ruta), 200, [
            'Content-Type' => $tipo ?? 'application/octet-stream',
        ]);

        $nombre = $this->nombreDescarga($nombre, $ruta);
        $respuesta->setContentDisposition(
            $tipo ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $nombre,
            $this->nombreAscii($nombre, $ruta),
        );

        // El navegador puede guardarla, pero cada vez pregunta al servidor:
        // con sesión recibe "no cambió" (304) sin volver a bajarla; sin
        // sesión, el login. "private" evita que el CDN la guarde.
        $respuesta->setPrivate();
        $respuesta->headers->addCacheControlDirective('no-cache');
        $respuesta->setAutoLastModified();
        $respuesta->isNotModified($request);

        return $respuesta;
    }

    /**
     * El nombre con que se subió, sin barras (el encabezado no las admite) y
     * con la extensión del archivo que se entrega: la foto subida como
     * "IMG_1234.jpg" se guarda en WebP, y descargada con .jpg no abriría.
     */
    private function nombreDescarga(?string $nombre, string $ruta): string
    {
        $nombre = trim(str_replace(['/', '\\'], '-', (string) $nombre));

        if ($nombre === '') {
            return basename($ruta);
        }

        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));

        if ($extension !== '' && strtolower(pathinfo($nombre, PATHINFO_EXTENSION)) !== $extension) {
            $nombre = pathinfo($nombre, PATHINFO_FILENAME).'.'.$extension;
        }

        return $nombre;
    }

    /** Versión ASCII del nombre, para navegadores viejos. */
    private function nombreAscii(string $nombre, string $ruta): string
    {
        $ascii = preg_replace('/[^\x20-\x7E]|%/', '', Str::ascii($nombre));

        return trim($ascii) !== '' ? $ascii : basename($ruta);
    }
}
