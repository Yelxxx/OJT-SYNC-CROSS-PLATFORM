<?php

/**
 * One-page A4 defense cheat card for OJT Sync (pure PHP, no PDF library).
 * Usage: php scripts/cheat-card.php [output.pdf]
 * Fixed two-column layout: purpose framing | lifecycle comparison, counters | Q&A, sound bites.
 */

$out = $argv[1] ?? 'docs/Defense-Cheat-Card.pdf';

// ---------- Page geometry (A4, points) ----------
const PAGE_W = 595.28;
const PAGE_H = 841.89;
const MARGIN = 40.0;
const GUTTER = 16.0;
const COL_W = (PAGE_W - 2 * MARGIN - GUTTER) / 2;

const NAVY = [0.118, 0.227, 0.373];
const MID = [0.173, 0.322, 0.510];
const BLUE = [0.290, 0.435, 0.647];
const BODY = [0.13, 0.15, 0.19];
const GRAY = [0.42, 0.45, 0.50];
const LIGHT = [0.55, 0.58, 0.62];

$HELV = explode(',', '278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584');
$HELVB = explode(',', '278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584');

function charW(int $ord, bool $bold): float
{
    global $HELV, $HELVB;
    if ($ord >= 32 && $ord <= 126) {
        return (float) ($bold ? $HELVB[$ord - 32] : $HELV[$ord - 32]);
    }
    $special = $bold
        ? [0x91 => 278, 0x92 => 278, 0x93 => 500, 0x94 => 500, 0x95 => 350, 0x96 => 556, 0x97 => 1000, 0xA0 => 278]
        : [0x91 => 222, 0x92 => 222, 0x93 => 333, 0x94 => 333, 0x95 => 350, 0x96 => 556, 0x97 => 1000, 0xA0 => 278];

    return (float) ($special[$ord] ?? 556);
}

function widthOf(string $word, float $size, bool $bold): float
{
    $w = 0.0;
    $n = strlen($word);
    for ($i = 0; $i < $n; $i++) {
        $w += charW(ord($word[$i]), $bold);
    }

    return $w * $size / 1000.0;
}

function spaceW(float $size): float
{
    return widthOf(' ', $size, false);
}

function esc(string $s): string
{
    $map = ["\u{2018}" => "\x91", "\u{2019}" => "\x92", "\u{201C}" => "\x93", "\u{201D}" => "\x94",
        "\u{2013}" => "\x96", "\u{2014}" => "\x97", "\u{2022}" => "\x95", "\u{00A0}" => ' ', "\u{2192}" => '->'];
    $s = strtr($s, $map);

    return addcslashes($s, "\\()");
}

// ---------- Content stream ----------
$ops = '';
$curColor = [0, 0, 0];

function setColor(array $rgb): void
{
    global $curColor;
    if ($rgb !== $curColor) {
        $GLOBALS['ops'] .= sprintf("%.3F %.3F %.3F rg\n", $rgb[0], $rgb[1], $rgb[2]);
        $curColor = $rgb;
    }
}

function text(float $x, float $y, string $str, float $size, bool $bold, array $color): void
{
    setColor($color);
    $GLOBALS['ops'] .= sprintf("BT /%s %.2F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
        $bold ? 'F2' : 'F1', $size, $x, PAGE_H - $y, esc($str));
}

function line(float $x1, float $y1, float $x2, float $y2, float $w, array $rgb): void
{
    setColor($rgb);
    $GLOBALS['ops'] .= sprintf("%.2F w %.2F %.2F m %.2F %.2F l S\n", $w, $x1, PAGE_H - $y1, $x2, PAGE_H - $y2);
}

function box(float $x, float $yTop, float $w, float $h, array $rgb): void
{
    setColor($rgb);
    $GLOBALS['ops'] .= sprintf("%.2F %.2F %.2F %.2F re f\n", $x, PAGE_H - $yTop - $h, $w, $h);
}

// ---------- Rich text helpers (supports **bold**) ----------
function tokensOf(string $text): array
{
    $out = [];
    $parts = preg_split('/(\*\*[^*]+\*\*)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $part) {
        if ($part === '' || $part === null) {
            continue;
        }
        if (preg_match('/^\*\*([^*]+)\*\*$/', $part, $m)) {
            $out[] = ['w' => $m[1], 'bold' => true];
        } else {
            foreach (preg_split('/\s+/', trim($part)) as $word) {
                if ($word !== '') {
                    $out[] = ['w' => $word, 'bold' => false];
                }
            }
        }
    }

    return $out;
}

/** Draw wrapped rich text; returns total height used. */
function drawRich(float $x, float $yTop, string $text, float $size, array $color, float $maxW, float $leading): float
{
    $lines = [];
    $cur = [];
    $curW = 0.0;
    foreach (tokensOf($text) as $t) {
        $w = widthOf($t['w'], $size, $t['bold']);
        if ($cur !== [] && $curW + spaceW($size) + $w > $maxW) {
            $lines[] = $cur;
            $cur = [$t];
            $curW = $w;
        } else {
            $curW = $cur === [] ? $w : $curW + spaceW($size) + $w;
            $cur[] = $t;
        }
    }
    if ($cur !== []) {
        $lines[] = $cur;
    }

    $y = $yTop;
    foreach ($lines as $ln) {
        $cx = $x;
        $y += $size + ($leading - $size);
        $cnt = count($ln);
        foreach ($ln as $i => $t) {
            text($cx, $y, $t['w'], $size, $t['bold'], $t['bold'] ? [0.10, 0.18, 0.30] : $color);
            $cx += widthOf($t['w'], $size, $t['bold']);
            if ($i < $cnt - 1) {
                $cx += spaceW($size);
            }
        }
    }

    return count($lines) * $leading;
}

// ---------- Section renderer ----------
$yL = PAGE_H - MARGIN; // left column cursor (top)
$yR = PAGE_H - MARGIN; // right column cursor (top)

function sectionHead(string $title, bool $left): void
{
    global $yL, $yR;
    $y = $left ? $yL : $yR;
    $x = $left ? MARGIN : MARGIN + COL_W + GUTTER;
    text($x, $y, $title, 10.5, true, NAVY);
    $y += 6;
    line($x, $y, $x + COL_W, $y, 1.4, NAVY);
    $y += 14;
    if ($left) {
        $yL = $y;
    } else {
        $yR = $y;
    }
}

function item(bool $left, string $label, string $body, float $size = 7.6, array $color = BODY): void
{
    global $yL, $yR;
    $y = $left ? $yL : $yR;
    $x = $left ? MARGIN : MARGIN + COL_W + GUTTER;
    text($x, $y, $label, $size, true, BLUE);
    $y += $size + 2.5;
    $h = drawRich($x, $y, $body, $size, $color, COL_W, $size * 1.3);
    $y += $h + 6.5;
    if ($left) {
        $yL = $y;
    } else {
        $yR = $y;
    }
}

function rule(bool $left): void
{
    global $yL, $yR;
    $y = $left ? $yL : $yR;
    $x = $left ? MARGIN : MARGIN + COL_W + GUTTER;
    line($x, $y, $x + COL_W, $y, 0.5, [0.82, 0.84, 0.86]);
    $y += 9;
    if ($left) {
        $yL = $y;
    } else {
        $yR = $y;
    }
}

// ---------- Header band ----------
box(0, 0, PAGE_W, 74, NAVY);
text(MARGIN, 30, 'OJT SYNC', 20, true, [1, 1, 1]);
text(MARGIN, 47, 'Thesis Defense Cheat Card  |  vs. CvSU Jobs Portal (cvsu.edu.ph/jobs)', 9, false, [0.78, 0.85, 0.93]);
text(PAGE_W - MARGIN - widthOf('Keep this face-up during the panel.', 8.5, false), 47, 'Keep this face-up during the panel.', 8.5, false, [0.78, 0.85, 0.93]);
line(0, 74, PAGE_W, 74, 2.5, [0.29, 0.42, 0.69]);

// ---------- Core one-liner (full width) ----------
$yL = 92;
text(MARGIN, $yL, 'THE ONE-LINER  (open with this)', 8, true, BLUE);
drawRich(MARGIN, $yL + 13,
    '**"The CvSU jobs page answers \'what jobs exist.** **OJT Sync answers \'did the student complete their hours, perform well, and get properly evaluated\' - and produces the records the university needs as evidence."**',
    9.2, NAVY, PAGE_W - 2 * MARGIN, 13);
rule(false);
$yL = $yL + 13 + 2 * 13 + 14;

// ---------- LEFT COLUMN ----------
sectionHead('THE REFRAME', true);
item(true, 'PORTAL = BULLETIN BOARD',
    'One-way announcements: static posts + PDF flyers. Applicant responds offline (email CV, walk-in). Nothing after "sees the post" is captured.');
item(true, 'OJT SYNC = WORKFLOW PLATFORM',
    'Transactional lifecycle: applications, verified DTR, e-journals, evaluations, coordinator monitoring - the full loop between Student, HR, and Coordinator.');
item(true, 'NOT COMPETITORS - COMPLEMENTS',
    'The QR poster + public company page covers dissemination; future work can feed postings back to the university page. Integration, not reinvention.');

sectionHead('WHAT ONLY OJT SYNC HAS', true);
item(true, '1. VERIFIED DTR', 'Check-in/out, computed hours vs. 300-hr requirement; **HR verifies** entries, coordinator reviews - not self-reported.');
item(true, '2. EVALUATION TOKENS', 'Single-use, anonymous, token-protected public links; rate-limited; reuse rejected.');
item(true, '3. COORDINATOR ANALYTICS', 'Institution-wide dashboard: hours completed vs. required, journals, evaluation status.');
item(true, '4. APPLICATION PIPELINE', 'One-click apply, resume upload, tracked status (pending > interview > hired/rejected).');
item(true, '5. PROOF IT WORKS', '**42 automated feature tests** - correctness demonstrated, not claimed.');

sectionHead('IF PRESSED', true);
item(true, '"JUST A JOB PORTAL CLONE?"', 'Novelty = the integrated verifiable OJT loop (points 1-4). A job board contributes zero records; mine produces accreditation evidence.');
item(true, '"SMARTPHONES AT WORK?"', 'DTR assumes workstation/HR-terminal check-in; paper remains fallback; future: HR-side recording.');
item(true, '"DATA PRIVACY?"', 'Role-scoped access, single-use anonymous tokens, no public personal data. Future: RA 10173 compliance docs.');

// ---------- RIGHT COLUMN ----------
sectionHead('SIDE-BY-SIDE', false);
item(false, 'JOBS PAGE', 'Public browsing + PDF flyers > offline application (email/walk-in). No status, no records.');
item(false, 'OJT SYNC', 'Search listings > apply in-system > tracked status > verified DTR > journals > evaluations > coordinator reports.');

sectionHead('QUICK ANSWERS', false);
item(false, 'Q: LIKE THE CvSU PAGE?', 'A: No - it is a static board; OJT Sync manages the lifecycle and produces university-required records.');
item(false, 'Q: WHY NOT EXTEND IT?', 'A: Complementary: it disseminates, mine operationalizes. Future: export/API feed back to the university page.');
item(false, 'Q: NOVELTY?', 'A: HR-verified DTR + single-use anonymous evaluation tokens + coordinator analytics in one auditable system.');
item(false, 'Q: DTR HONESTY?', 'A: HR verification before hours count; coordinator review as second layer.');
item(false, 'Q: EVALUATION INTEGRITY?', 'A: Single-use anonymous tokens, rate-limited routes; second submission rejected.');
item(false, 'Q: SDLC MODEL?', 'A: State it from your proposal (e.g., Agile/Incremental). Adjust before printing.');

sectionHead('CLOSING LINE', false);
text(MARGIN + COL_W + GUTTER, $yR, '"The university page tells students that jobs exist.', 8.2, false, BODY);
$yR += 11.5;
text(MARGIN + COL_W + GUTTER, $yR, 'OJT Sync carries the student from application', 8.2, false, BODY);
$yR += 11.5;
text(MARGIN + COL_W + GUTTER, $yR, 'through verified attendance, journaling, and', 8.2, false, BODY);
$yR += 11.5;
text(MARGIN + COL_W + GUTTER, $yR, 'supervision evaluation - the layer the portal', 8.2, false, BODY);
$yR += 11.5;
text(MARGIN + COL_W + GUTTER, $yR, 'does not have."', 8.2, false, BODY);
$yR += 14;

rule(false);

// Balance column ends
$yL = max($yL, PAGE_H - 60);
$yR = max($yR, PAGE_H - 60);
text(MARGIN, PAGE_H - 46, 'Sources: cvsu.edu.ph/jobs (Sept 2026) + OJT Sync codebase review.', 6.8, false, LIGHT);
text(PAGE_W - MARGIN - widthOf('Generated September 2026', 6.8, false), PAGE_H - 46, 'Generated September 2026', 6.8, false, LIGHT);

// ---------- Assemble PDF ----------
$content = $ops;
$pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
$offsets = [];
$addObject = function (int $num, string $body) use (&$pdf, &$offsets): void {
    $offsets[$num] = strlen($pdf);
    $pdf .= $num . " 0 obj\n" . $body . "\nendobj\n";
};
$fontObj = fn (string $base): string => "<< /Type /Font /Subtype /Type1 /BaseFont /" . $base . " /Encoding /WinAnsiEncoding >>";

$addObject(1, '<< /Type /Catalog /Pages 2 0 R >>');
$addObject(2, '<< /Type /Pages /Kids [ 5 0 R ] /Count 1 >>');
$addObject(3, $fontObj('Helvetica'));
$addObject(4, $fontObj('Helvetica-Bold'));
$addObject(5, "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents 6 0 R >>");
$addObject(6, "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream");

$xrefPos = strlen($pdf);
$pdf .= "xref\n0 7\n0000000000 65535 f \n";
for ($i = 1; $i <= 6; $i++) {
    $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
}
$pdf .= "trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";

if (is_file($out)) {
    unlink($out);
}
file_put_contents($out, $pdf);
echo "Created {$out} (1 page, " . strlen($pdf) . " bytes)\n";
