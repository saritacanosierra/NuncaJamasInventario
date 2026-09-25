<?php
/**
 * Arma un PDF de una tabla. El archivo queda cerrado: no es una hoja editable.
 */

class PdfTabla {
    public function documento($titulo, $subtitulo, array $columnas, array $filas) {
        $horizontal = count($columnas) > 6;
        $ancho = $horizontal ? 841.89 : 595.28;
        $alto = $horizontal ? 595.28 : 841.89;
        $margen = 28;
        $tamano = $horizontal ? 7.5 : 8.5;
        $util = $ancho - ($margen * 2);
        $anchos = $this->anchos($columnas, $filas, $util);
        $paginas = [];
        $comandos = [];
        $y = $alto - $margen;

        $abrir = function () use (&$comandos, &$y, $alto, $margen, $titulo, $subtitulo) {
            $comandos = [];
            $y = $alto - $margen;
            $comandos[] = $this->texto($margen, $y - 12, $titulo, 13, true);
            $y -= 28;
            $comandos[] = $this->texto($margen, $y - 8, $subtitulo, 8, false);
            $y -= 18;
        };
        $abrir();
        $this->encabezado($comandos, $y, $margen, $anchos, $columnas, $tamano);

        if ($filas === []) {
            $filas = [array_fill(0, count($columnas), '')];
            $filas[0][0] = 'No hay registros en este mes.';
        }

        foreach ($filas as $fila) {
            $celdas = [];
            $lineasMax = 1;
            foreach ($columnas as $i => $columna) {
                $texto = isset($fila[$i]) ? (string) $fila[$i] : '';
                $lineas = $this->lineas($texto, $anchos[$i] - 6, $tamano);
                $celdas[] = $lineas;
                $lineasMax = max($lineasMax, count($lineas));
            }
            $altoFila = ($lineasMax * ($tamano + 2)) + 6;
            if ($y - $altoFila < $margen) {
                $paginas[] = implode("\n", $comandos);
                $abrir();
                $this->encabezado($comandos, $y, $margen, $anchos, $columnas, $tamano);
            }
            $y -= $altoFila;
            $x = $margen;
            foreach ($columnas as $i => $columna) {
                $comandos[] = sprintf('0.95 0.93 0.93 RG 0.4 w %.2F %.2F %.2F %.2F re S', $x, $y, $anchos[$i], $altoFila);
                $derecha = $this->aLaDerecha($columna);
                $lineaY = $y + $altoFila - $tamano - 4;
                foreach ($celdas[$i] as $linea) {
                    $dx = 3;
                    if ($derecha) {
                        $dx = max(3, $anchos[$i] - 3 - ($this->medir($linea) * $tamano * 0.46));
                    }
                    $comandos[] = $this->texto($x + $dx, $lineaY, $linea, $tamano, false);
                    $lineaY -= $tamano + 2;
                }
                $x += $anchos[$i];
            }
        }

        $paginas[] = implode("\n", $comandos);
        return $this->compilar($paginas, $ancho, $alto);
    }

    private function encabezado(&$comandos, &$y, $margen, array $anchos, array $columnas, $tamano) {
        $altoFila = $tamano + 10;
        $y -= $altoFila;
        $x = $margen;
        foreach ($columnas as $i => $columna) {
            $comandos[] = sprintf('0.988 0.820 0.820 rg %.2F %.2F %.2F %.2F re f', $x, $y, $anchos[$i], $altoFila);
            $comandos[] = sprintf('0.75 0.62 0.62 RG 0.4 w %.2F %.2F %.2F %.2F re S', $x, $y, $anchos[$i], $altoFila);
            $comandos[] = $this->texto($x + 3, $y + 4, $columna, $tamano, true);
            $x += $anchos[$i];
        }
    }

    private function anchos(array $columnas, array $filas, $util) {
        $pesos = [];
        foreach ($columnas as $i => $columna) {
            $peso = max(6, mb_strlen((string) $columna));
            foreach ($filas as $fila) {
                $texto = isset($fila[$i]) ? (string) $fila[$i] : '';
                $peso = max($peso, min(28, mb_strlen($texto)));
            }
            if ($this->aLaDerecha($columna)) {
                $peso = min($peso, 12);
            }
            $pesos[] = $peso;
        }
        $suma = array_sum($pesos) ?: 1;
        $anchos = [];
        $usado = 0;
        $ultimo = count($pesos) - 1;
        foreach ($pesos as $i => $peso) {
            if ($i === $ultimo) {
                $anchos[] = $util - $usado;
            } else {
                $ancho = round($util * $peso / $suma, 2);
                $anchos[] = $ancho;
                $usado += $ancho;
            }
        }
        return $anchos;
    }

    private function aLaDerecha($columna) {
        return in_array($columna, [
            'Monto', 'Subtotal', 'Descuento', 'IVA', 'Domicilio', 'Empaque', 'Total',
            'Efectivo', 'Transferencia', 'Tarjeta', 'Cantidad', 'Precio', 'IVA incluido',
            'Precio base', 'Precio con IVA', 'Subtotal base', 'Subtotal con IVA', 'Entrada', 'Salida',
        ], true);
    }

    private function lineas($texto, $ancho, $tamano) {
        $texto = trim(preg_replace('/\s+/u', ' ', (string) $texto));
        if ($texto === '') {
            return [''];
        }
        $max = max(4, (int) floor($ancho / ($tamano * 0.46)));
        $palabras = preg_split('/ /u', $texto);
        $lineas = [];
        $actual = '';
        foreach ($palabras as $palabra) {
            if (mb_strlen($palabra) > $max) {
                if ($actual !== '') {
                    $lineas[] = $actual;
                    $actual = '';
                }
                $lineas[] = mb_substr($palabra, 0, max(1, $max - 3)) . '...';
                if (count($lineas) >= 2) {
                    return array_slice($lineas, 0, 2);
                }
                continue;
            }
            $candidato = $actual === '' ? $palabra : $actual . ' ' . $palabra;
            if (mb_strlen($candidato) <= $max) {
                $actual = $candidato;
            } else {
                $lineas[] = $actual;
                $actual = $palabra;
                if (count($lineas) >= 2) {
                    return array_slice($lineas, 0, 2);
                }
            }
        }
        if ($actual !== '' && count($lineas) < 2) {
            $lineas[] = $actual;
        }
        return $lineas ?: [''];
    }

    private function medir($texto) {
        return mb_strlen((string) $texto);
    }

    private function texto($x, $y, $texto, $tamano, $negrita) {
        $fuente = $negrita ? '/F2' : '/F1';
        $limpio = $this->win($texto);
        return sprintf(
            '0.20 0.13 0.13 rg BT %s %.2F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET',
            $fuente,
            $tamano,
            $x,
            $y,
            $limpio
        );
    }

    private function win($texto) {
        $bytes = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', (string) $texto);
        if ($bytes === false) {
            $bytes = '';
        }
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $bytes);
    }

    private function compilar(array $paginas, $ancho, $alto) {
        $objetos = [];
        $objetos[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $n = count($paginas);
        $primerPagina = 5;
        $kids = [];
        for ($i = 0; $i < $n; $i++) {
            $kids[] = ($primerPagina + ($i * 2)) . ' 0 R';
        }
        $objetos[] = '<< /Type /Pages /Count ' . $n . ' /Kids [' . implode(' ', $kids) . '] >>';
        $objetos[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objetos[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        foreach ($paginas as $i => $contenido) {
            $paginaId = $primerPagina + ($i * 2);
            $flujoId = $paginaId + 1;
            $objetos[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $ancho . ' ' . $alto . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $flujoId . ' 0 R >>';
            $objetos[] = "<< /Length " . strlen($contenido) . " >>\nstream\n" . $contenido . "\nendstream";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objetos as $i => $cuerpo) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $cuerpo . "\nendobj\n";
        }
        $inicio = strlen($pdf);
        $total = count($objetos) + 1;
        $pdf .= "xref\n0 " . $total . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $total; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . $total . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $inicio . "\n%%EOF";
        return $pdf;
    }
}
