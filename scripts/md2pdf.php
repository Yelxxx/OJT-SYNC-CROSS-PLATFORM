<?php

/**
 * Minimal Markdown -> PDF converter for the OJT Sync docs.
 * Usage: php scripts/md2pdf.php <input.md> <output.pdf> [Title] [Subtitle]
 * Pure PHP (no PDF library): streams PDF 1.4 with the base-14 fonts.
 * Supports: h1-h4, paragraphs, bold/italic/code, bullet/numbered lists,
 * GitHub tables, blockquotes, horizontal rules, page numbers in the footer.
 */

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/md2pdf.php <input.md> <output.pdf> [Title] [Subtitle]\n");
    exit(1);
}

[$_, $in, $out] = $argv;
$titleArg = $argv[3] ?? null;
$subtitleArg = $argv[4] ?? null;

// ---------- Page geometry (A4, points) ----------
const PAGE_W = 595.28;
const PAGE_H = 841.89;
const MARGIN = 54.0;
const CONTENT_W = PAGE_W - 2 * MARGIN;
const BOTTOM_LIMIT = MARGIN + 30.0;

// ---------- Font metrics (chars 32..126) ----------
$HELV = explode(',', '278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584');
$HELVB = explode(',', '278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584');

// Special CP1252 glyphs (Helvetica / Helvetica-Bold)
$SPECIAL_REG = [0x91 => 222, 0x92 => 222, 0x93 => 333, 0x94 => 333, 0x95 => 350, 0x96 => 556, 0x97 => 1000, 0x85 => 1000, 0xA0 => 278];
$SPECIAL_BOLD = [0x91 => 278, 0x92 => 278, 0x93 => 500, 0x94 => 500, 0x95 => 350, 0x96 => 556, 0x97 => 1000, 0x85 => 1000, 0xA0 => 278];

function fontKey(array $st): string
{
    if (!empty($st['code'])) {
        return 'F4';
    }
    if (!empty($st['bold'])) {
        return 'F2';
    }
    if (!empty($st['italic'])) {
        return 'F3';
    }

    return 'F1';
}

function charW(int $ord, array $st): float
{
    global $HELV, $HELVB, $SPECIAL_REG, $SPECIAL_BOLD;
    if (!empty($st['code'])) {
        return 600.0; // Courier monospace
    }
    $table = !empty($st['bold']) ? $HELVB : $HELV;
    $special = !empty($st['bold']) ? $SPECIAL_BOLD : $SPECIAL_REG;
    if ($ord >= 32 && $ord <= 126) {
        return (float) $table[$ord - 32];
    }

    return (float) ($special[$ord] ?? 556);
}

function widthOf(string $word, float $size, array $st): float
{
    $w = 0.0;
    $bytes = strlen($word);
    for ($i = 0; $i < $bytes; $i++) {
        $w += charW(ord($word[$i]), $st);
    }

    return $w * $size / 1000.0;
}

function spaceW(float $size, array $st): float
{
    return widthOf(' ', $size, $st);
}

// ---------- Text encoding ----------
function toWinAnsi(string $t): string
{
    if (!preg_match('/[\x80-\xFF]/', $t)) {
        return $t;
    }
    $conv = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $t);
    if ($conv !== false && $conv !== '') {
        return $conv;
    }
    $map = [
        "\u{2018}" => "\x91", "\u{2019}" => "\x92", "\u{201C}" => "\x93", "\u{201D}" => "\x94",
        "\u{2013}" => "\x96", "\u{2014}" => "\x97", "\u{2022}" => "\x95", "\u{2026}" => "\x85",
        "\u{00A0}" => ' ', "\u{20B1}" => 'PhP',
    ];
    $out = '';
    preg_match_all('/./u', $t, $m);
    foreach ($m[0] as $ch) {
        if (strlen($ch) === 1) {
            $out .= $ch;
        } elseif (isset($map[$ch])) {
            $out .= $map[$ch];
        } else {
            $out .= '?';
        }
    }

    return $out;
}

function pdfEscape(string $s): string
{
    return addcslashes(toWinAnsi($s), "\\()");
}

// ---------- Content-stream state ----------
$pages = [];
$page = '';
$y = PAGE_H - MARGIN;
$curColor = [0.0, 0.0, 0.0];

function out(string $s): void
{
    global $page;
    $page .= $s;
}

function setColor(array $rgb): void
{
    global $curColor;
    if ($rgb !== $curColor) {
        out(sprintf("%.3F %.3F %.3F rg\n", $rgb[0], $rgb[1], $rgb[2]));
        $curColor = $rgb;
    }
}

function textAt(float $x, float $baselineY, string $str, array $st): void
{
    setColor($st['color'] ?? [0, 0, 0]);
    out(sprintf(
        "BT /%s %.2F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
        fontKey($st),
        $st['size'],
        $x,
        PAGE_H - $baselineY,
        pdfEscape($str)
    ));
}

function rectAt(float $x, float $yTop, float $w, float $h, array $rgb): void
{
    setColor($rgb);
    out(sprintf("%.2F %.2F %.2F %.2F re f\n", $x, PAGE_H - $yTop - $h, $w, $h));
}

function newPage(): void
{
    global $pages, $page, $y;
    if ($page !== '') {
        $pages[] = $page;
    }
    $page = '';
    $y = PAGE_H - MARGIN;
}

// ---------- Inline markdown -> styled tokens ----------
function runs(string $text, array $base = []): array
{
    $segments = [];
    $parts = preg_split('/(\*\*[^*]+\*\*|\*[^*]+\*|`[^`]+`)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $part) {
        if ($part === '' || $part === null) {
            continue;
        }
        if (preg_match('/^\*\*([^*]+)\*\*$/', $part, $m)) {
            $segments[] = array_merge($base, ['text' => $m[1], 'bold' => true]);
        } elseif (preg_match('/^\*([^*]+)\*$/', $part, $m)) {
            $segments[] = array_merge($base, ['text' => $m[1], 'italic' => true]);
        } elseif (preg_match('/^`([^`]+)`$/', $part, $m)) {
            $segments[] = array_merge($base, ['text' => $m[1], 'code' => true, 'color' => [0.70, 0.29, 0.0]]);
        } else {
            $segments[] = array_merge($base, ['text' => $part]);
        }
    }

    return $segments;
}

function tokens(array $segments): array
{
    $words = [];
    foreach ($segments as $seg) {
        $text = $seg['text'];
        unset($seg['text']);
        foreach (preg_split('/\s+/', trim($text)) as $w) {
            if ($w === '') {
                continue;
            }
            $words[] = ['w' => $w] + $seg;
        }
    }

    return $words;
}

function wrapTokens(array $tokens, float $maxW, float $size): array
{
    $lines = [];
    $cur = [];
    $curW = 0.0;
    foreach ($tokens as $t) {
        $w = widthOf($t['w'], $size, $t);
        if ($cur !== [] && $curW + spaceW($size, $t) + $w > $maxW) {
            $lines[] = $cur;
            $cur = [$t];
            $curW = $w;
        } else {
            $curW = $cur === [] ? $w : $curW + spaceW($size, $t) + $w;
            $cur[] = $t;
        }
    }
    if ($cur !== []) {
        $lines[] = $cur;
    }

    return $lines;
}

function drawLine(float $x, float $baselineY, array $line, float $size, array $color): void
{
    $count = count($line);
    foreach ($line as $i => $t) {
        textAt($x, $baselineY, $t['w'], $t + ['size' => $size, 'color' => $color]);
        $x += widthOf($t['w'], $size, $t);
        if ($i < $count - 1) {
            $x += spaceW($size, $t);
        }
    }
}

// ---------- Blocks ----------
function paraBlock(string $text, array $opts = []): float
{
    global $y;
    $size = $opts['size'] ?? 9.8;
    $leading = $opts['leading'] ?? $size * 1.42;
    $before = $opts['before'] ?? 0.0;
    $after = $opts['after'] ?? 7.0;
    $indent = $opts['indent'] ?? 0.0;
    $color = $opts['color'] ?? [0.13, 0.15, 0.19];

    $base = [];
    if (!empty($opts['bold'])) {
        $base['bold'] = true;
    }
    if (!empty($opts['italic'])) {
        $base['italic'] = true;
    }
    $lines = wrapTokens(tokens(runs($text, $base)), CONTENT_W - $indent, $size);
    $need = count($lines) * $leading + $before + $after + ($opts['keep'] ?? 0.0);
    if ($y - $need < BOTTOM_LIMIT) {
        newPage();
    }
    $y -= $before;
    $firstBaseline = $y - $leading;
    foreach ($lines as $line) {
        $y -= $leading;
        drawLine(MARGIN + $indent, $y, $line, $size, $color);
    }
    $y -= $after;

    return $firstBaseline;
}

function centeredBlock(string $text, array $opts = []): void
{
    global $y;
    $size = $opts['size'] ?? 19.0;
    $leading = $opts['leading'] ?? $size * 1.35;
    $color = $opts['color'] ?? [0.118, 0.227, 0.373];
    $base = [];
    if (!empty($opts['bold'])) {
        $base['bold'] = true;
    }
    if (!empty($opts['italic'])) {
        $base['italic'] = true;
    }
    $lines = wrapTokens(tokens(runs($text, $base)), CONTENT_W, $size);
    if ($y - (count($lines) * $leading + $opts['after']) < BOTTOM_LIMIT) {
        newPage();
    }
    foreach ($lines as $line) {
        $lineW = 0.0;
        $count = count($line);
        foreach ($line as $i => $t) {
            $lineW += widthOf($t['w'], $size, $t) + ($i < $count - 1 ? spaceW($size, $t) : 0);
        }
        $y -= $leading;
        drawLine(MARGIN + max(0.0, (CONTENT_W - $lineW) / 2), $y, $line, $size, $color);
    }
    $y -= $opts['after'];
}

function quoteBlock(string $text): void
{
    global $y;
    $size = 9.8;
    $leading = 14.0;
    $indent = 14.0;
    $color = [0.23, 0.27, 0.33];
    $lines = wrapTokens(tokens(runs($text)), CONTENT_W - $indent, $size);
    $height = count($lines) * $leading;
    if ($y - $height - 14 < BOTTOM_LIMIT) {
        newPage();
    }
    $y -= 6;
    $top = $y;
    foreach ($lines as $line) {
        $y -= $leading;
        drawLine(MARGIN + $indent, $y, $line, $size, $color);
    }
    // accent bar on the left
    setColor([0.29, 0.42, 0.69]);
    out(sprintf("1.2 w %.2F %.2F m %.2F %.2F l S\n", MARGIN + 2, PAGE_H - $top + 3, MARGIN + 2, PAGE_H - $y - 3));
    $y -= 10;
}

function hrBlock(): void
{
    global $y;
    if ($y - 18 < BOTTOM_LIMIT) {
        newPage();
    }
    $y -= 9;
    setColor([0.82, 0.84, 0.86]);
    out(sprintf("0.7 w %.2F %.2F m %.2F %.2F l S\n", MARGIN, PAGE_H - $y, PAGE_W - MARGIN, PAGE_H - $y));
    $y -= 9;
}

function cells(string $line): array
{
    return array_map(
        fn (string $c): string => trim(trim($c), '`'),
        explode('|', trim($line, '|'))
    );
}

function tableBlock(array $rows): void
{
    global $y;
    $size = 9.0;
    $leading = 12.0;
    $pad = 4.0;
    $header = cells(array_shift($rows));
    array_shift($rows); // separator row

    $n = count($header);
    $natural = array_fill(0, $n, 0.0);
    $measure = function (array $cellsRow) use (&$natural, $size, $pad): void {
        foreach ($cellsRow as $i => $cell) {
            foreach (tokens(runs($cell)) as $t) {
                $natural[$i] = max($natural[$i], widthOf($t['w'], $size, $t) + 2 * $pad);
            }
        }
    };
    $measure($header);
    foreach ($rows as $r) {
        $measure(cells($r));
    }

    $total = max(1.0, array_sum($natural));
    $equal = CONTENT_W / $n;
    $cols = [];
    foreach ($natural as $w) {
        $cols[] = max(60.0, 0.45 * $equal + 0.55 * ($w / $total) * CONTENT_W);
    }
    // rescale clamped widths back into the content width, fix the last column
    $sum = array_sum($cols);
    $cols = array_map(fn (float $c): float => $c * CONTENT_W / $sum, $cols);
    $last = CONTENT_W - array_sum(array_slice($cols, 0, $n - 1));
    if ($last < 50.0) {
        $widest = array_search(max($cols), $cols, true);
        if ($widest !== null && $widest !== $n - 1) {
            $cols[$widest] -= 50.0 - $last;
            $last = 50.0;
        }
    }
    $cols[$n - 1] = $last;

    $drawRow = function (array $cellsRow, bool $isHeader, int $ri = 0) use (&$y, &$header, &$drawRow, $cols, $size, $leading, $pad, $n): void {
        $cellLines = [];
        $maxLines = 1;
        foreach ($cellsRow as $i => $cell) {
            $st = $isHeader ? ['bold' => true] : [];
            $lines = wrapTokens(tokens(runs($cell, $st)), $cols[$i] - 2 * $pad, $size);
            $cellLines[$i] = ['lines' => $lines, 'st' => $st];
            $maxLines = max($maxLines, count($lines));
        }
        $rowH = $maxLines * $leading + 2 * $pad;

        if ($y - $rowH < BOTTOM_LIMIT) {
            newPage();
            if (!$isHeader) {
                $drawRow($header, true); // repeat header on the new page
            }
        }

        if ($isHeader) {
            rectAt(MARGIN, $y, CONTENT_W, $rowH, [0.945, 0.949, 0.961]);
        } elseif ($ri % 2 === 1) {
            rectAt(MARGIN, $y, CONTENT_W, $rowH, [0.973, 0.973, 0.969]);
        }

        $top = $y;
        foreach ($cellLines as $i => $data) {
            $x = MARGIN + array_sum(array_slice($cols, 0, $i));
            $color = $isHeader ? [0.12, 0.15, 0.20] : [0.13, 0.15, 0.19];
            foreach ($data['lines'] as $k => $line) {
                drawLine($x + $pad, $top + $pad + $size + $k * $leading, $line, $size, $color + ($isHeader ? [] : $data['st']));
            }
        }
        $y -= $rowH;

        // grid
        $path = sprintf("%.2F %.2F m %.2F %.2F l\n", MARGIN, PAGE_H - $top, MARGIN + CONTENT_W, PAGE_H - $top);
        $path .= sprintf("%.2F %.2F m %.2F %.2F l\n", MARGIN, PAGE_H - $y, MARGIN + CONTENT_W, PAGE_H - $y);
        $acc = MARGIN;
        for ($i = 0; $i <= $n; $i++) {
            $path .= sprintf("%.2F %.2F m %.2F %.2F l\n", $acc, PAGE_H - $top, $acc, PAGE_H - $y);
            $acc += $cols[$i] ?? 0.0;
        }
        setColor([0.72, 0.72, 0.72]);
        out("0.5 w\n" . $path . "S\n");
    };

    $drawRow($header, true);
    foreach ($rows as $ri => $r) {
        $drawRow(cells($r), false, (int) $ri);
    }
    $y -= 10;
}

// ---------- Parse the markdown ----------
$md = file_get_contents($in);
$md = strtr($md, ["\u{2192}" => '->', "\u{2713}" => '(ok)']);
$lines = preg_split('/\R/', $md);

if ($titleArg !== null) {
    centeredBlock($titleArg, ['size' => 19, 'leading' => 26, 'after' => 6, 'bold' => true]);
    if ($subtitleArg !== null) {
        centeredBlock($subtitleArg, ['size' => 10.5, 'leading' => 15, 'after' => 12, 'color' => [0.42, 0.45, 0.50]]);
    }
    hrBlock();
}

$numberCounter = 0;
$skipFirstH1 = $titleArg !== null;
$count = count($lines);
for ($i = 0; $i < $count; $i++) {
    $line = rtrim($lines[$i]);

    if ($line === '' || $line === '---') {
        if ($line === '---') {
            hrBlock();
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
        tableBlock($block);
        continue;
    }

    // Headings
    if (preg_match('/^(#{1,4})\s+(.*)$/', $line, $m)) {
        $level = strlen($m[1]);
        $text = $m[2];
        if ($level === 1 && $skipFirstH1) {
            $skipFirstH1 = false;
            continue;
        }
        match ($level) {
            1 => paraBlock($text, ['size' => 15.5, 'leading' => 20, 'before' => 6, 'after' => 8, 'color' => [0.118, 0.227, 0.373], 'keep' => 34, 'bold' => true]),
            2 => paraBlock($text, ['size' => 12.5, 'leading' => 16.5, 'before' => 16, 'after' => 6, 'color' => [0.173, 0.322, 0.510], 'keep' => 30, 'bold' => true]),
            3 => paraBlock($text, ['size' => 11, 'leading' => 14.5, 'before' => 14, 'after' => 5, 'color' => [0.290, 0.435, 0.647], 'keep' => 26, 'bold' => true]),
            default => paraBlock($text, ['size' => 10, 'leading' => 13.5, 'before' => 12, 'after' => 4, 'color' => [0.42, 0.45, 0.50], 'keep' => 24, 'bold' => true, 'italic' => true]),
        };
        continue;
    }

    // Bullet lists
    if (preg_match('/^\s*[-*]\s+(.*)$/', $line, $m)) {
        $firstBase = paraBlock($m[1], ['indent' => 14, 'after' => 3]);
        textAt(MARGIN + 2, $firstBase, "\x95", ['size' => 9.8, 'color' => [0.55, 0.58, 0.62]]);
        continue;
    }

    // Numbered lists
    if (preg_match('/^\s*(\d+)\.\s+(.*)$/', $line, $m)) {
        $numberCounter = (int) $m[1];
        $firstBase = paraBlock($m[2], ['indent' => 18, 'after' => 3]);
        textAt(MARGIN + 2, $firstBase, $numberCounter . '.', ['size' => 9.8, 'color' => [0.42, 0.45, 0.50]]);
        continue;
    }

    // Blockquotes (consecutive '>' lines join into one paragraph)
    if (str_starts_with($line, '>')) {
        $quoteText = [];
        while ($i < $count && str_starts_with(rtrim($lines[$i]), '>')) {
            $quoteText[] = ltrim(rtrim($lines[$i]), '> ');
            $i++;
        }
        $i--;
        quoteBlock(trim(implode(' ', $quoteText)));
        continue;
    }

    // Italic-only paragraph (e.g. the sources line)
    if (preg_match('/^\*[^*]+\*$/', $line)) {
        paraBlock($line, ['color' => [0.42, 0.45, 0.50], 'before' => 4, 'after' => 14]);
        continue;
    }

    // Paragraph (join consecutive non-special lines)
    $para = [$line];
    while (
        $i + 1 < $count && rtrim($lines[$i + 1]) !== '' && rtrim($lines[$i + 1]) !== '---'
        && !str_starts_with(rtrim($lines[$i + 1]), '|') && !preg_match('/^(#{1,4})\s/', rtrim($lines[$i + 1]))
        && !preg_match('/^\s*([-*]|\d+\.)\s/', rtrim($lines[$i + 1])) && !str_starts_with(rtrim($lines[$i + 1]), '>')
    ) {
        $para[] = rtrim($lines[++$i]);
    }
    paraBlock(implode(' ', $para));
}

newPage(); // flush the last page
$nPages = count($pages);

// ---------- Footer on every page ----------
for ($k = 0; $k < $nPages; $k++) {
    $left = "OJT Sync - Thesis Defense Guide";
    $right = 'Page ' . ($k + 1) . ' of ' . $nPages;
    $st = ['size' => 8, 'color' => [0.55, 0.58, 0.62]];
    $footer = sprintf(
        "BT /F1 8 Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\nBT /F1 8 Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
        MARGIN,
        34,
        pdfEscape($left),
        PAGE_W - MARGIN - widthOf($right, 8, $st),
        34,
        pdfEscape($right)
    );
    $pages[$k] = $footer . $pages[$k];
}

// ---------- Assemble the PDF ----------
$pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
$offsets = [];
$addObject = function (int $num, string $body) use (&$pdf, &$offsets): void {
    $offsets[$num] = strlen($pdf);
    $pdf .= $num . " 0 obj\n" . $body . "\nendobj\n";
};
$fontObj = fn (string $base): string => "<< /Type /Font /Subtype /Type1 /BaseFont /" . $base . " /Encoding /WinAnsiEncoding >>";

$addObject(1, '<< /Type /Catalog /Pages 2 0 R >>');
$kids = implode(' ', array_map(fn ($k): string => (7 + 2 * $k) . ' 0 R', range(0, $nPages - 1)));
$addObject(2, "<< /Type /Pages /Kids [ {$kids} ] /Count {$nPages} >>");
$addObject(3, $fontObj('Helvetica'));
$addObject(4, $fontObj('Helvetica-Bold'));
$addObject(5, $fontObj('Helvetica-Oblique'));
$addObject(6, $fontObj('Courier'));

foreach ($pages as $k => $content) {
    $pageObj = 7 + 2 * $k;
    $contentObj = 8 + 2 * $k;
    $addObject($pageObj, "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] "
        . "/Resources << /Font << /F1 3 0 R /F2 4 0 R /F3 5 0 R /F4 6 0 R >> >> /Contents {$contentObj} 0 R >>");
    $addObject($contentObj, "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream");
}

$xrefPos = strlen($pdf);
$maxObj = 6 + 2 * $nPages;
$pdf .= "xref\n0 " . ($maxObj + 1) . "\n0000000000 65535 f \n";
for ($i = 1; $i <= $maxObj; $i++) {
    $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
}
$pdf .= "trailer\n<< /Size " . ($maxObj + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";

if (is_file($out)) {
    unlink($out);
}
file_put_contents($out, $pdf);
echo "Created {$out} ({$nPages} pages, " . strlen($pdf) . " bytes)\n";
