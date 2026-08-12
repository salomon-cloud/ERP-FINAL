<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReporteExcelService
{
    public function download(
        string $filename,
        string $titulo,
        array $encabezados,
        iterable $filas,
        array $columnasMoneda = [],
        ?array $totales = null,
        ?string $subtitulo = null,
    ): BinaryFileResponse {
        $spreadsheet = new Spreadsheet;
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle(mb_substr('Reporte', 0, 31));

        $filasNormalizadas = [];
        foreach ($filas as $filaDato) {
            $filasNormalizadas[] = array_values((array) $filaDato);
        }

        $colLast = $this->letraColumna(count($encabezados) - 1);
        $fila = 1;

        $hoja->setCellValue("A{$fila}", $titulo);
        $hoja->mergeCells("A{$fila}:{$colLast}{$fila}");
        $this->estiloRango($hoja, "A{$fila}:{$colLast}{$fila}", bold: true, size: 14, fill: '1D4E89', fontColor: 'FFFFFF');
        $hoja->getRowDimension($fila)->setRowHeight(30);
        $fila++;

        if ($subtitulo) {
            $hoja->setCellValue("A{$fila}", $subtitulo);
            $hoja->mergeCells("A{$fila}:{$colLast}{$fila}");
            $this->estiloRango($hoja, "A{$fila}:{$colLast}{$fila}", italic: true, size: 10, fontColor: '6B7280');
            $fila++;
        }

        $filaEnc = $fila;
        foreach ($encabezados as $i => $encabezado) {
            $hoja->setCellValue($this->letraColumna($i).$fila, $encabezado);
        }
        $this->estiloRango($hoja, "A{$fila}:{$colLast}{$fila}", bold: true, fill: '0E4280', fontColor: 'FFFFFF', center: true);
        $hoja->getRowDimension($fila)->setRowHeight(22);
        $fila++;

        $filaIni = $fila;
        foreach ($filasNormalizadas as $filaDato) {
            foreach ($filaDato as $i => $valor) {
                $celda = $this->letraColumna($i).$fila;
                $hoja->setCellValue($celda, $valor);
                if (in_array($i, $columnasMoneda, true)) {
                    $hoja->getStyle($celda)->getNumberFormat()->setFormatCode('"$"#,##0.00');
                }
            }
            $fila++;
        }

        if ($totales) {
            foreach ($encabezados as $i => $_) {
                $celda = $this->letraColumna($i).$fila;
                $hoja->setCellValue($celda, $totales[$i] ?? '');
                if (in_array($i, $columnasMoneda, true) && isset($totales[$i])) {
                    $hoja->getStyle($celda)->getNumberFormat()->setFormatCode('"$"#,##0.00');
                }
            }
            $this->estiloRango($hoja, "A{$fila}:{$colLast}{$fila}", bold: true, fill: 'DBEAFE');
            $fila++;
        }

        $ultimoDato = $totales ? $fila - 2 : $fila - 1;
        if ($ultimoDato >= $filaIni) {
            $hoja->getStyle("A{$filaIni}:{$colLast}{$ultimoDato}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('B0B7C3');

            $hoja->getStyle("A{$filaIni}:{$colLast}{$ultimoDato}")
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);

            for ($f = $filaIni + 1; $f <= $ultimoDato; $f += 2) {
                $hoja->getStyle('A'.$f.':'.$colLast.$f)
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F3F6FB');
            }

            foreach ($columnasMoneda as $i) {
                $hoja->getStyle($this->letraColumna($i).$filaIni.':'.$this->letraColumna($i).$ultimoDato)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }

        foreach ($encabezados as $i => $encabezado) {
            $col = $this->letraColumna($i);
            $ancho = mb_strlen((string) $encabezado);
            foreach ($filasNormalizadas as $filaDato) {
                $ancho = max($ancho, mb_strlen((string) ($filaDato[$i] ?? '')));
            }
            $hoja->getColumnDimension($col)->setWidth(min(max($ancho + 3, 10), 45));
        }

        $hoja->freezePane('A'.($filaEnc + 1));
        $hoja->setAutoFilter("A{$filaEnc}:{$colLast}{$filaEnc}");

        $pageSetup = $hoja->getPageSetup();
        $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);
        $pageSetup->setRowsToRepeatAtTopByStartAndEnd($filaEnc, $filaEnc);

        $hoja->getPageMargins()->setTop(0.5);
        $hoja->getPageMargins()->setRight(0.3);
        $hoja->getPageMargins()->setLeft(0.3);
        $hoja->getPageMargins()->setBottom(0.5);

        $ruta = tempnam(sys_get_temp_dir(), 'sisen_').'.xlsx';
        (new Xlsx($spreadsheet))->save($ruta);

        return new BinaryFileResponse($ruta, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
        ], true);
    }

    private function estiloRango(
        Worksheet $hoja,
        string $rango,
        bool $bold = false,
        bool $italic = false,
        int $size = 0,
        ?string $fill = null,
        ?string $fontColor = null,
        bool $center = false,
    ): void {
        $estilo = $hoja->getStyle($rango);
        $fuente = $estilo->getFont();

        if ($bold) {
            $fuente->setBold(true);
        }
        if ($italic) {
            $fuente->setItalic(true);
        }
        if ($size) {
            $fuente->setSize($size);
        }
        if ($fontColor) {
            $fuente->getColor()->setARGB($fontColor);
        }
        if ($fill) {
            $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($fill);
        }
        if ($center) {
            $estilo->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }
    }

    private function letraColumna(int $indice): string
    {
        $letra = '';
        while ($indice >= 0) {
            $letra = chr(65 + ($indice % 26)).$letra;
            $indice = intdiv($indice, 26) - 1;
        }

        return $letra;
    }
}
