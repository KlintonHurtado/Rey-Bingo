<?php

namespace App\Libraries;

use CodeIgniter\HTTP\ResponseInterface;

class ExcelExport
{
    /**
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     * @param array{numeric_columns?: list<int>, integer_columns?: list<int>, sheet_name?: string, title?: string} $options
     */
    public function build(array $headers, array $rows, array $options = []): string
    {
        $sheetName = $this->sanitizeSheetName($options['sheet_name'] ?? 'Datos');
        $numericColumns = array_map('intval', $options['numeric_columns'] ?? []);
        $integerColumns = array_map('intval', $options['integer_columns'] ?? []);
        $title = trim((string) ($options['title'] ?? ''));

        $widths = $this->calculateColumnWidths($headers, $rows, $numericColumns, $integerColumns);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"';
        $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"';
        $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"';
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"';
        $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";

        $xml .= $this->buildStyles();
        $xml .= '<Worksheet ss:Name="' . $this->escape($sheetName) . '">' . "\n";
        $xml .= '<Table>' . "\n";

        foreach ($widths as $width) {
            $xml .= '<Column ss:AutoFitWidth="0" ss:Width="' . $width . '"/>' . "\n";
        }

        if ($title !== '') {
            $xml .= '<Row ss:StyleID="Title" ss:Height="28">' . "\n";
            $xml .= '<Cell ss:MergeAcross="' . max(0, count($headers) - 1) . '"><Data ss:Type="String">'
                . $this->escape($title)
                . '</Data></Cell>' . "\n";
            $xml .= '</Row>' . "\n";

            $xml .= '<Row ss:StyleID="Subtitle" ss:Height="18">' . "\n";
            $xml .= '<Cell ss:MergeAcross="' . max(0, count($headers) - 1) . '"><Data ss:Type="String">'
                . $this->escape('Generado: ' . date('d/m/Y H:i:s') . ' | Filas: ' . count($rows))
                . '</Data></Cell>' . "\n";
            $xml .= '</Row>' . "\n";

            $xml .= '<Row ss:Height="8"><Cell><Data ss:Type="String"></Data></Cell></Row>' . "\n";
        }

        $xml .= '<Row ss:StyleID="Header" ss:Height="24">' . "\n";
        foreach ($headers as $header) {
            $xml .= '<Cell><Data ss:Type="String">' . $this->escape((string) $header) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";

        foreach ($rows as $rowIndex => $row) {
            $styleId = ($rowIndex % 2) === 1 ? 'Zebra' : 'Default';
            $xml .= '<Row ss:StyleID="' . $styleId . '" ss:Height="20">' . "\n";

            foreach ($headers as $colIndex => $_header) {
                $value = $row[$colIndex] ?? '';
                $xml .= $this->buildCell($value, $colIndex, $numericColumns, $integerColumns, $styleId === 'Zebra');
            }

            $xml .= '</Row>' . "\n";
        }

        $xml .= '</Table>' . "\n";
        $xml .= '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">' . "\n";
        $xml .= '<FreezePanes/><FrozenNoSplit/><SplitHorizontal>' . ($title !== '' ? 4 : 1) . '</SplitHorizontal>' . "\n";
        $xml .= '<TopRowBottomPane>' . ($title !== '' ? 4 : 1) . '</TopRowBottomPane>' . "\n";
        $xml .= '<ActivePane>2</ActivePane>' . "\n";
        $xml .= '</WorksheetOptions>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        $xml .= '</Workbook>';

        return $xml;
    }

    /**
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     */
    public function downloadResponse(array $headers, array $rows, string $filename, array $options = []): ResponseInterface
    {
        if (! str_ends_with(strtolower($filename), '.xls')) {
            $filename .= '.xls';
        }

        $xml = $this->build($headers, $rows, $options);

        return service('response')
            ->setHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0, no-cache, must-revalidate')
            ->setHeader('Pragma', 'public')
            ->setBody($xml);
    }

    /**
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     * @param list<int> $numericColumns
     * @param list<int> $integerColumns
     * @return list<int>
     */
    private function calculateColumnWidths(array $headers, array $rows, array $numericColumns, array $integerColumns): array
    {
        $widths = [];

        foreach ($headers as $colIndex => $header) {
            $maxLen = mb_strlen((string) $header);

            foreach ($rows as $row) {
                $value = $row[$colIndex] ?? '';
                if ($value === null || $value === '') {
                    continue;
                }

                if (in_array($colIndex, $numericColumns, true) || in_array($colIndex, $integerColumns, true)) {
                    $text = is_numeric($value)
                        ? number_format((float) $value, in_array($colIndex, $integerColumns, true) ? 0 : 2, '.', ',')
                        : (string) $value;
                } else {
                    $text = (string) $value;
                }

                $maxLen = max($maxLen, mb_strlen($text));
            }

            // Anchos pensados para Excel: fechas, montos, detalle, etc.
            if (in_array($colIndex, $numericColumns, true)) {
                $width = max(90, min(130, $maxLen * 8 + 28));
            } elseif (in_array($colIndex, $integerColumns, true)) {
                $width = max(70, min(110, $maxLen * 8 + 24));
            } elseif ($maxLen > 80) {
                $width = 360; // Detalle / textos largos visibles
            } elseif ($maxLen > 40) {
                $width = max(180, min(280, $maxLen * 7 + 30));
            } else {
                $width = max(85, min(200, $maxLen * 8 + 28));
            }

            $widths[] = (int) round($width);
        }

        return $widths;
    }

    private function buildStyles(): string
    {
        return <<<XML
<Styles>
    <Style ss:ID="Default">
        <Alignment ss:Vertical="Center" ss:WrapText="1"/>
        <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#1F2937"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
        </Borders>
    </Style>
    <Style ss:ID="Title">
        <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
        <Font ss:Bold="1" ss:FontName="Calibri" ss:Size="14" ss:Color="#4C1D95"/>
    </Style>
    <Style ss:ID="Subtitle">
        <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
        <Font ss:FontName="Calibri" ss:Size="10" ss:Color="#6B7280"/>
    </Style>
    <Style ss:ID="Header">
        <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
        <Font ss:Bold="1" ss:Color="#FFFFFF" ss:FontName="Calibri" ss:Size="11"/>
        <Interior ss:Color="#6236FF" ss:Pattern="Solid"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#4C1D95"/>
            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#4C1D95"/>
        </Borders>
    </Style>
    <Style ss:ID="Zebra">
        <Alignment ss:Vertical="Center" ss:WrapText="1"/>
        <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#1F2937"/>
        <Interior ss:Color="#F5F3FF" ss:Pattern="Solid"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
        </Borders>
    </Style>
    <Style ss:ID="Money">
        <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
        <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#065F46"/>
        <NumberFormat ss:Format="#,##0.00"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
        </Borders>
    </Style>
    <Style ss:ID="MoneyZebra">
        <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
        <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#065F46"/>
        <Interior ss:Color="#F5F3FF" ss:Pattern="Solid"/>
        <NumberFormat ss:Format="#,##0.00"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
        </Borders>
    </Style>
    <Style ss:ID="Integer">
        <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        <Font ss:FontName="Calibri" ss:Size="11"/>
        <NumberFormat ss:Format="0"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
        </Borders>
    </Style>
    <Style ss:ID="IntegerZebra">
        <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        <Font ss:FontName="Calibri" ss:Size="11"/>
        <Interior ss:Color="#F5F3FF" ss:Pattern="Solid"/>
        <NumberFormat ss:Format="0"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
        </Borders>
    </Style>
</Styles>

XML;
    }

    /**
     * @param list<int> $numericColumns
     * @param list<int> $integerColumns
     */
    private function buildCell(mixed $value, int $colIndex, array $numericColumns, array $integerColumns, bool $zebra = false): string
    {
        if ($value === null || $value === '') {
            return '<Cell><Data ss:Type="String"></Data></Cell>' . "\n";
        }

        if (in_array($colIndex, $integerColumns, true) && is_numeric($value)) {
            $style = $zebra ? 'IntegerZebra' : 'Integer';

            return '<Cell ss:StyleID="' . $style . '"><Data ss:Type="Number">'
                . $this->escapeNumber((float) $value, 0)
                . '</Data></Cell>' . "\n";
        }

        if (in_array($colIndex, $numericColumns, true) && is_numeric($value)) {
            $style = $zebra ? 'MoneyZebra' : 'Money';

            return '<Cell ss:StyleID="' . $style . '"><Data ss:Type="Number">'
                . $this->escapeNumber((float) $value)
                . '</Data></Cell>' . "\n";
        }

        return '<Cell><Data ss:Type="String">' . $this->escape((string) $value) . '</Data></Cell>' . "\n";
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function escapeNumber(float $value, int $decimals = 2): string
    {
        return htmlspecialchars(number_format($value, $decimals, '.', ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function sanitizeSheetName(string $name): string
    {
        $name = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $name) ?? 'Datos';
        $name = trim($name);

        return mb_substr($name !== '' ? $name : 'Datos', 0, 31);
    }
}
