<?php

/**
 * Minimal Markdown -> DOCX converter for the OJT Sync docs.
 * Usage: php scripts/md2docx.php <input.md> <output.docx> [Title] [Subtitle]
 * Supports: h1-h4, paragraphs, bold/italic/code, bullet/numbered lists,
 * GitHub tables, blockquotes, checklists, horizontal rules.
 */

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/md2docx.php <input.md> <output.docx> [Title] [Subtitle]\n");
    exit(1);
}

[$_, $in, $out] = $argv;
$title = $argv[3] ?? null;
$subtitle = $argv[4] ?? null;
$md = file_get_contents($in);
$lines = preg_split('/\R/', $md);

function esc(string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

/** Parse inline markdown (bold, italic, code) into run XML. */
function runs(string $text): string {
    $xml = '';
    $parts = preg_split('/(\*\*[^*]+\*\*|\*[^*]+\*|`[^`]+`)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $part) {
        if ($part === '' || $part === null) {
            continue;
        }
        if (preg_match('/^\*\*([^*]+)\*\*$/', $part, $m)) {
            $xml .= '<w:r><w:rPr><w:b/></w:rPr><w:t xml:space="preserve">'.esc($m[1]).'</w:t></w:r>';
        } elseif (preg_match('/^\*([^*]+)\*$/', $part, $m)) {
            $xml .= '<w:r><w:rPr><w:i/></w:rPr><w:t xml:space="preserve">'.esc($m[1]).'</w:t></w:r>';
        } elseif (preg_match('/^`([^`]+)`$/', $part, $m)) {
            $xml .= '<w:r><w:rPr><w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/><w:color w:val="B34A00"/></w:rPr><w:t xml:space="preserve">'.esc($m[1]).'</w:t></w:r>';
        } else {
            $xml .= '<w:r><w:t xml:space="preserve">'.esc($part).'</w:t></w:r>';
        }
    }

    return $xml;
}

function para(string $text, string $style = '', string $align = ''): string {
    $pPr = '<w:pPr>';
    if ($style !== '') {
        $pPr .= "<w:pStyle w:val=\"{$style}\"/>";
    }
    if ($align !== '') {
        $pPr .= "<w:jc w:val=\"{$align}\"/>";
    }
    $pPr .= '</w:pPr>';

    return '<w:p>'.($text === '' ? $pPr : $pPr.runs($text)).'</w:p>';
}

function heading(string $text, int $level): string {
    return para($text, 'Heading'.$level);
}

$numberCounter = 0;

function bullet(string $text): string {
    return '<w:p><w:pPr><w:pStyle w:val="ListParagraph"/></w:pPr>'
        .'<w:r><w:t xml:space="preserve">\u{2022}  </w:t></w:r>'.runs($text).'</w:p>';
}

function numbered(string $text): string {
    global $numberCounter;
    $numberCounter++;

    return '<w:p><w:pPr><w:pStyle w:val="ListParagraph"/></w:pPr>'
        .'<w:r><w:t xml:space="preserve">'.$numberCounter.'.  </w:t></w:r>'.runs($text).'</w:p>';
}

function checkbox(string $text): string {
    return '<w:p><w:pPr><w:pStyle w:val="ListParagraph"/><w:ind w:left="720"/></w:pPr>'
        .'<w:r><w:rPr><w:color w:val="9CA3AF"/></w:rPr><w:t xml:space="preserve">'."☐ ".'</w:t></w:r>'
        .runs($text).'</w:p>';
}

function quote(string $text): string {
    return '<w:p><w:pPr><w:pStyle w:val="Quote"/></w:pPr>'.runs($text).'</w:p>';
}

function hr(): string {
    return '<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="6" w:space="1" w:color="D1D5DB"/></w:pBdr></w:pPr></w:p>';
}

/** Parse a GitHub-style table block (array of lines) into a DOCX table. */
function table(array $rows): string {
    $cells = fn (string $line): array => array_map(
        fn (string $c): string => trim(trim($c), '`'),
        array_slice(explode('|', trim($line, '|')), 0, -0)
    );
    $header = $cells(array_shift($rows));
    array_shift($rows); // separator row (---|---)

    $xml = '<w:tbl><w:tblPr><w:tblStyle w:val="TableGrid"/><w:tblW w:w="0" w:type="auto"/><w:tblBorders>'
        .'<w:top w:val="single" w:sz="4" w:color="D1D5DB"/><w:left w:val="single" w:sz="4" w:color="D1D5DB"/>'
        .'<w:bottom w:val="single" w:sz="4" w:color="D1D5DB"/><w:right w:val="single" w:sz="4" w:color="D1D5DB"/>'
        .'<w:insideH w:val="single" w:sz="4" w:color="D1D5DB"/><w:insideV w:val="single" w:sz="4" w:color="D1D5DB"/>'
        .'</w:tblBorders></w:tblPr>';

    $xml .= '<w:tr><w:trPr><w:tblHeader/></w:trPr>';
    foreach ($header as $cell) {
        $xml .= '<w:tc><w:tcPr><w:shd w:val="clear" w:fill="F3F0E8"/></w:tcPr><w:p><w:pPr><w:spacing w:before="60" w:after="60"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t xml:space="preserve">'.esc($cell).'</w:t></w:r></w:p></w:tc>';
    }
    $xml .= '</w:tr>';

    foreach ($rows as $row) {
        $xml .= '<w:tr>';
        foreach ($cells($row) as $cell) {
            $xml .= '<w:tc><w:p><w:pPr><w:spacing w:before="40" w:after="40"/></w:pPr>'.runs($cell).'</w:p></w:tc>';
        }
        $xml .= '</w:tr>';
    }

    return $xml.'</w:tbl>';
}

// ---- Parse markdown into block XML ------------------------------------
$body = '';

// Title page
if ($title !== null) {
    $body .= para('', '', '').para(esc($title), 'Title');
    if ($subtitle !== null) {
        $body .= para(esc($subtitle), 'Subtitle');
    }
}

$count = count($lines);
$numberCounter = 0;
$skipFirstH1 = $title !== null;
for ($i = 0; $i < $count; $i++) {
    $line = rtrim($lines[$i]);

    if ($line === '' || $line === '---') {
        if ($line === '---') {
            $body .= hr();
        }
        $numberCounter = 0;
        continue;
    }

    // Tables
    if (str_starts_with($line, '|')) {
        $block = [];
        while ($i < $count && str_starts_with(rtrim($lines[$i]), '|')) {
            $block[] = rtrim($lines[$i]);
            $i++;
        }
        $i--;
        $body .= table($block);
        continue;
    }

    // Headings
    if (preg_match('/^(#{1,4})\s+(.*)$/', $line, $m)) {
        if (strlen($m[1]) === 1 && $skipFirstH1) {
            $skipFirstH1 = false;
            continue;
        }
        $body .= heading($m[2], strlen($m[1]));
        continue;
    }

    // Checklists
    if (preg_match('/^\s*-\s+\[ \]\s+(.*)$/', $line, $m)) {
        $body .= checkbox($m[1]);
        continue;
    }

    // Bullet lists (incl. plain bullets)
    if (preg_match('/^\s*[-*]\s+(.*)$/', $line, $m)) {
        $body .= bullet($m[1]);
        continue;
    }

    // Numbered lists
    if (preg_match('/^\s*\d+\.\s+(.*)$/', $line, $m)) {
        $body .= numbered($m[1]);
        continue;
    }

    // Blockquotes (may wrap several lines)
    if (str_starts_with($line, '>')) {
        $quoteText = [];
        while ($i < $count && str_starts_with(rtrim($lines[$i]), '>')) {
            $quoteText[] = ltrim(rtrim($lines[$i]), '> ');
            $i++;
        }
        $i--;
        foreach (preg_split('/\R/', trim(implode(' ', $quoteText))) as $qLine) {
            $body .= quote($qLine);
        }
        continue;
    }

    $body .= para($line);
}

// ---- Build the docx zip --------------------------------------------------
$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    .'<Default Extension="xml" ContentType="application/xml"/>'
    .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
    .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
    .'</Types>';

$rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
    .'</Relationships>';

$docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    .'</Relationships>';

$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    .'<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/></w:rPr></w:rPrDefault>'
    .'<w:pPrDefault><w:pPr><w:spacing w:after="120"/></w:pPr></w:pPrDefault></w:docDefaults>'
    .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>'
    .'<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:before="240" w:after="120"/></w:pPr><w:rPr><w:b/><w:sz w:val="56"/><w:color w:val="1E3A5F"/></w:rPr></w:style>'
    .'<w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:basedOn w:val="Normal"/><w:rPr><w:i/><w:sz w:val="26"/><w:color w:val="6B7280"/></w:rPr></w:style>'
    .'<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:outlineLvl w:val="0"/><w:spacing w:before="360" w:after="160"/></w:pPr><w:rPr><w:b/><w:sz w:val="36"/><w:color w:val="1E3A5F"/></w:rPr></w:style>'
    .'<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:outlineLvl w:val="1"/><w:spacing w:before="300" w:after="140"/></w:pPr><w:rPr><w:b/><w:sz w:val="30"/><w:color w:val="2C5282"/></w:rPr></w:style>'
    .'<w:style w:type="paragraph" w:styleId="Heading3"><w:name w:val="heading 3"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:outlineLvl w:val="2"/><w:spacing w:before="240" w:after="120"/></w:pPr><w:rPr><w:b/><w:sz w:val="26"/><w:color w:val="4A6FA5"/></w:rPr></w:style>'
    .'<w:style w:type="paragraph" w:styleId="Heading4"><w:name w:val="heading 4"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:outlineLvl w:val="3"/></w:pPr><w:rPr><w:b/><w:i/><w:color w:val="6B7280"/></w:rPr></w:style>'
    .'<w:style w:type="paragraph" w:styleId="ListParagraph"><w:name w:val="List Paragraph"/><w:basedOn w:val="Normal"/><w:pPr><w:ind w:left="720"/></w:pPr></w:style>'
    .'<w:style w:type="paragraph" w:styleId="Quote"><w:name w:val="Quote"/><w:basedOn w:val="Normal"/><w:pPr><w:ind w:left="560"/><w:spacing w:after="160"/></w:pPr><w:rPr><w:i/><w:color w:val="374151"/></w:rPr></w:style>'
    .'</w:styles>';

$document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    .'<w:body>'.$body
    .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/></w:sectPr>'
    .'</w:body></w:document>';

if (is_file($out)) {
    unlink($out);
}

$zip = new ZipArchive();
if ($zip->open($out, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "Cannot create {$out}\n");
    exit(1);
}

$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', $rootRels);
$zip->addFromString('word/_rels/document.xml.rels', $docRels);
$zip->addFromString('word/styles.xml', $styles);
$zip->addFromString('word/document.xml', $document);
$zip->close();

echo "Created {$out}\n";
