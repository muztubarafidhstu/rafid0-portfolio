<?php
/** Reusable card components for the public site. */

function project_card(array $p): string
{
    $href = url('project.php?slug=' . rawurlencode($p['slug']));
    $img = media_url($p['cover_image']);
    $html = '<article class="card project-card">';
    $html .= '<a class="card-media" href="' . e($href) . '" tabindex="-1" aria-hidden="true">';
    $html .= $img !== ''
        ? '<img src="' . e($img) . '" alt="" loading="lazy">'
        : '<span class="media-fallback">' . icon('box') . '</span>';
    $html .= '</a><div class="card-body">';
    $html .= '<h3 class="card-title"><a href="' . e($href) . '">' . e($p['title']) . '</a>' . icon('arrow-right') . '</h3>';
    $tags = array_slice(tags($p['tags']), 0, 3);
    if ($tags) {
        $html .= '<div class="chips">';
        foreach ($tags as $t) {
            $html .= '<span class="chip">' . e($t) . '</span>';
        }
        $html .= '</div>';
    }
    $html .= '</div></article>';
    return $html;
}

function research_card(array $r): string
{
    $href = url('research-item.php?slug=' . rawurlencode($r['slug']));
    $cls = status_class($r['status']);
    $img = media_url($r['image']);
    $meta = trim($r['venue'] . ($r['year'] !== '' ? ' ' . $r['year'] : ''));
    $btn = $cls === 'green' ? 'View Paper' : ($cls === 'blue' ? 'View Progress' : 'View Details');
    $html = '<article class="card research-card tone-' . $cls . '">';
    if ($img !== '') {
        $html .= '<img class="research-bg" src="' . e($img) . '" alt="" loading="lazy">';
    }
    $html .= '<div class="research-body">';
    $html .= '<span class="status-label">' . e($r['status']) . '</span>';
    $html .= '<h3><a href="' . e($href) . '">' . e($r['title']) . '</a></h3>';
    if ($meta !== '') {
        $html .= '<p class="muted small">' . e($meta) . '</p>';
    }
    $html .= '<a class="btn btn-sm btn-tone" href="' . e($href) . '">' . e($btn) . ' ' . icon('arrow-right') . '</a>';
    $html .= '</div></article>';
    return $html;
}

function section_head(string $title, string $linkText = '', string $linkHref = ''): string
{
    $html = '<div class="section-head"><h2>' . e($title) . '</h2>';
    if ($linkText !== '') {
        $html .= '<a class="link-accent" href="' . e(url($linkHref)) . '">' . e($linkText) . ' ' . icon('arrow-right') . '</a>';
    }
    return $html . '</div>';
}

/** Small SVG line chart from a list of numbers. */
function line_chart_svg(array $values, string $label = ''): string
{
    $values = array_values(array_filter(array_map('trim', $values), 'is_numeric'));
    $values = array_map('floatval', $values);
    $n = count($values);
    if ($n < 2) {
        return '';
    }
    $w = 520; $h = 200; $pl = 36; $pr = 12; $pt = 14; $pb = 28;
    $min = min($values); $max = max($values);
    $pad = ($max - $min) * 0.15 ?: 1;
    $lo = floor($min - $pad); $hi = ceil($max + $pad);
    $x = function ($i) use ($n, $w, $pl, $pr) { return $pl + ($w - $pl - $pr) * $i / ($n - 1); };
    $y = function ($v) use ($lo, $hi, $h, $pt, $pb) { return $pt + ($h - $pt - $pb) * (1 - ($v - $lo) / ($hi - $lo)); };
    $pts = [];
    foreach ($values as $i => $v) {
        $pts[] = round($x($i), 1) . ',' . round($y($v), 1);
    }
    $grid = '';
    for ($g = 0; $g <= 4; $g++) {
        $gv = $lo + ($hi - $lo) * $g / 4;
        $gy = round($y($gv), 1);
        $grid .= '<line x1="' . $pl . '" x2="' . ($w - $pr) . '" y1="' . $gy . '" y2="' . $gy . '" class="chart-grid"/>';
        $grid .= '<text x="' . ($pl - 6) . '" y="' . ($gy + 3) . '" text-anchor="end" class="chart-axis">' . e(rtrim(rtrim(number_format($gv, 1), '0'), '.')) . '</text>';
    }
    $xl = '';
    $step = max(1, (int) ceil($n / 6));
    for ($i = 0; $i < $n; $i += $step) {
        $xl .= '<text x="' . round($x($i), 1) . '" y="' . ($h - 8) . '" text-anchor="middle" class="chart-axis">' . ($i + 1) . '</text>';
    }
    $dots = '';
    foreach ($values as $i => $v) {
        $dots .= '<circle cx="' . round($x($i), 1) . '" cy="' . round($y($v), 1) . '" r="3" class="chart-dot"><title>Run ' . ($i + 1) . ': ' . e((string) $v) . '</title></circle>';
    }
    $area = $pl . ',' . ($h - $pb) . ' ' . implode(' ', $pts) . ' ' . ($w - $pr) . ',' . ($h - $pb);
    return '<figure class="chart"><svg viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . e($label ?: 'Results chart') . '">'
        . $grid . $xl
        . '<polygon points="' . $area . '" class="chart-area"/>'
        . '<polyline points="' . implode(' ', $pts) . '" class="chart-line"/>' . $dots
        . '</svg>' . ($label !== '' ? '<figcaption>' . e($label) . '</figcaption>' : '') . '</figure>';
}
