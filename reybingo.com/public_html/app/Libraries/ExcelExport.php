<?php

namespace App\Libraries;

use CodeIgniter\HTTP\ResponseInterface;

class ExcelExport
{
    /**
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     * @param array{numeric_columns?: list<int>, integer_columns?: list<int>, sheet_name?: string} $options
     */
    public function build(array $headers, array $rows, array $options = []): string
    {
        $sheetName = $this->sanitizeSheetName($options['sheet_name'] ?? 'Datos');
        $numericColumns = array_map('intval', $options['numeric_columns'] ?? []);
        $integerColumns = array_map('intval', $options['integer_columns'] ?? []);

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

        foreach ($headers as $header) {
            $width = max(70, min(260, mb_strlen((string) $header) * 9 + 24));
            $xml .= '<Column ss:AutoFitWidth="1" ss:Width="' . $width . '"/>' . "\n";
        }

        $xml .= '<Row ss:StyleID="Header" ss:Height="22">' . "\n";
        foreach ($headers as $header) {
            $xml .= '<Cell><Data ss:Type="String">' . $this->escape((string) $header) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";

        foreach ($rows as $rowIndex => $row) {
            $styleId = ($rowIndex % 2) === 1 ? 'Zebra' : 'Default';
            $xml .= '<Row ss:StyleID="' . $styleId . '">' . "\n";

            foreach ($headers as $colIndex => $_header) {
                $value = $row[$colIndex] ?? '';
                $xml .= $this->buildCell($value, $colIndex, $numericColumns, $integerColumns, $styleId === 'Zebra');
            }

            $xml .= '</Row>' . "\n";
        }

        $xml .= '</Table>' . "\n";
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

    private function buildStyles(): string
    {
        return <<<XML
<Styles>
    <Style ss:ID="Default">
        <Alignment ss:Vertical="Center" ss:WrapText="1"/>
        <Font ss:FontName="Calibri" ss:Size="11"/>
    </Style>
    <Style ss:ID="Header">
        <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
        <Font ss:Bold="1" ss:Color="#FFFFFF" ss:FontName="Calibri" ss:Size="11"/>
        <Interior ss:Color="#5B21B6" ss:Pattern="Solid"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#4C1D95"/>
        </Borders>
    </Style>
    <Style ss:ID="Zebra">
        <Alignment ss:Vertical="Center" ss:WrapText="1"/>
        <Font ss:FontName="Calibri" ss:Size="11"/>
        <Interior ss:Color="#F5F3FF" ss:Pattern="Solid"/>
    </Style>
    <Style ss:ID="Money">
        <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
        <Font ss:FontName="Calibri" ss:Size="11"/>
        <NumberFormat ss:Format="#,##0.00"/>
    </Style>
    <Style ss:ID="MoneyZebra">
        <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
        <Font ss:FontName="Calibri" ss:Size="11"/>
        <Interior ss:Color="#F5F3FF" ss:Pattern="Solid"/>
        <NumberFormat ss:Format="#,##0.00"/>
    </Style>
    <Style ss:ID="Integer">
        <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        <Font ss:FontName="Calibri" ss:Size="11"/>
        <NumberFormat ss:Format="0"/>
    </Style>
    <Style ss:ID="IntegerZebra">
        <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        <Font ss:FontName="Calibri" ss:Size="11"/>
        <Interior ss:Color="#F5F3FF" ss:Pattern="Solid"/>
        <NumberFormat ss:Format="0"/>
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
